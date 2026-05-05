<?php

namespace App\Services;

use App\Mail\GeneralOperationalMail;
use App\Models\Accessory;
use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\License;
use App\Models\ReturnRequest;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ReturnRequestService
{
    public function listAll()
    {
        return ReturnRequest::with(['asset.status', 'sender', 'actionedBy'])
            ->latest()
            ->get()
            ->map(fn($r) => $this->mapReturnRequest($r));
    }

    public function listMine(int $userId)
    {
        return ReturnRequest::with(['asset.status', 'sender', 'actionedBy'])
            ->where(function ($q) use ($userId) {
                $q->where('Employee_ID', $userId)
                    ->orWhere('Sender_ID', $userId);
            })
            ->latest()
            ->get()
            ->map(fn($r) => $this->mapReturnRequest($r));
    }

    public function getMyAssets(int $userId)
    {
        return Asset::with(['status', 'category', 'locationModel'])
            ->where('Employee_ID', $userId)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'model' => $a->Asset_Name,
                'serial' => $a->Serial_No,
                'barcode' => $a->barcode,
                'category' => $a->category?->name ?? $a->Asset_Category,
                'location' => $a->locationModel?->name ?? $a->Location,
                'status' => $a->status?->Status_Name ?? 'Assigned',
            ]);
    }

    public function createRequest(User $user, array $data): ReturnRequest
    {
        // Requirement 3: Support flexible reporting (Dropdown or Manual Type-in)
        $asset = null;
        if (!empty($data['asset_id'])) {
            $asset = Asset::find($data['asset_id']);
            if ($asset && (int) $asset->Employee_ID !== (int) $user->id) {
                abort(response()->json(['message' => 'You can only request return for assets assigned to you.'], 403));
            }
        }

        $assetDesc = $asset ? $asset->Asset_Name : ($data['asset_name'] ?? 'Multiple Items');

        // pick a sensible status even if the database hasn't been seeded with the
        // expected 'Pending'/'Requested' entries. fall back to the asset's status, or
        // finally just the very first status row or 1 to satisfy NOT NULL.
        $statusId = Status::firstOf(['Pending', 'Requested'])
            ?? $asset?->Status_ID
            ?? Status::first()?->id
            ?? 1;

        $returnRequest = ReturnRequest::create([
            'Asset_ID' => $asset?->id,
            'Employee_ID' => $user->id,
            'Sender_ID' => $user->id,
            'Status_ID' => $statusId,
            'Request_Date' => now(),
            'Workflow_Status' => 'pending_inspection',
            'Sender_Condition' => $data['sender_condition'] ?? 'Good',
            'Missing_Items' => $data['missing_items'] ?? [],
            'Items' => $data['items'] ?? [],
            'Notes' => trim(collect([
                $data['notes'] ?? null,
                !empty($data['issue_notes']) ? ('Reported issue: ' . $data['issue_notes']) : null,
            ])->filter()->implode(' | ')) ?: null,
            'reason' => $data['reason'] ?? null,
        ]);

        // send a quick confirmation email to the requester
        if ($user->email) {
            // Requirement 2: Remove IDs from title/labels
            Mail::to($user->email)->send(new GeneralOperationalMail(
                $user,
                'Return Request Logged',
                'Request Received',
                "Your return request for '{$assetDesc}' has been received and is awaiting administrative inspection.",
                [
                    ['label' => 'Equipment', 'value' => $assetDesc],
                    ['label' => 'Status', 'value' => 'Pending Inspection'],
                ],
                'View Workspace',
                config('app.url') . '/dashboard/user/workspace'
            ));
        }

        // notify administrators about the new request as well
        $admins = User::whereIn('role', ['admin', 'superadmin'])->get()->filter(fn($u) => $u->email);
        if ($admins->isNotEmpty()) {
            $subject = 'New Return Request Submitted';
            $details = "A new return request has been submitted by {$user->name}.\n";
            if ($asset) {
                $details .= "Asset: {$asset->Asset_Name}\n";
            } else {
                $details .= 'Items: ' . $assetDesc . "\n";
            }
            $details .= 'Please log in to review and inspect.';

            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new GeneralOperationalMail(
                    $admin,
                    'New Return Request',
                    'Action Required',
                    "A new return request has been submitted by {$user->name} and requires inspection.",
                    [
                        ['label' => 'Staff', 'value' => $user->name],
                        ['label' => 'Equipment', 'value' => $assetDesc],
                        ['label' => 'Condition', 'value' => $data['sender_condition'] ?? 'Not specified'],
                    ],
                    'Review Request',
                    config('app.url') . '/dashboard/admin/movements'
                ));
            }
        }

        ActivityLog::create([
            'asset_id' => $asset?->id,
            'Employee_ID' => $user->id,
            'user_name' => $user->name,
            'action' => 'Return Requested',
            'target_type' => $asset ? 'Asset' : 'Mixed Items',
            'target_name' => $assetDesc,
            'details' => "Return request submitted for admin inspection",
        ]);

        return $returnRequest->fresh(['asset.status', 'sender', 'actionedBy']);
    }

    public function updateStatus(int $id, string $status, ?string $reason = null): ReturnRequest
    {
        $request = ReturnRequest::with('asset')->findOrFail($id);
        $lower = strtolower($status);

        // handle acceptance / rejection
        if (in_array($lower, ['accepted', 'rejected'], true)) {
            $asset = $request->asset;

            // ONLY unassign items if the request was ACCEPTED
            if ($lower === 'accepted') {

                // 1. Unassign primary hardware asset
                if ($asset) {
                    $asset->update([
                        'Employee_ID' => null,
                        'Status_ID' => Status::firstOf(['Ready to Deploy', 'Available']) ?? $asset->Status_ID,
                    ]);
                }

                // 2. Unassign secondary items (Components, Accessories, Consumables, Licenses)
                $sender = User::find($request->Sender_ID);
                if ($sender && !empty($request->Items) && is_array($request->Items)) {
                    foreach ($request->Items as $itm) {
                        $type = $itm['type'] ?? null;
                        $itemId = $itm['id'] ?? null;
                        if (!$type || !$itemId) {
                            continue;
                        }

                        switch ($type) {

                            case 'accessory':
                                $acc = Accessory::find($itemId);
                                if ($acc) {
                                    $this->returnItemToStock($sender->accessories(), 'accessory_id', $itemId);
                                    $acc->increment('remaining_qty');
                                }
                                break;
                            case 'consumable':
                                $cons = Consumable::find($itemId);
                                if ($cons) {
                                    $this->returnItemToStock($sender->consumables(), 'consumable_id', $itemId);
                                    $cons->increment('in_stock');
                                }
                                break;
                            case 'license':
                                $lic = License::find($itemId);
                                if ($lic) {
                                    $pivot = $sender->licenses()->where('license_id', $itemId)->wherePivotNull('returned_at')->first();
                                    if ($pivot) {
                                        $sender->licenses()->updateExistingPivot($itemId, ['returned_at' => now()]);
                                        $lic->increment('remaining_seats');
                                    }
                                }
                                break;
                        }
                    }
                }
            }

            // attach rejection reason if supplied
            if ($lower === 'rejected' && $reason) {
                $request->Notes = trim(collect([$request->Notes, 'Rejection reason: ' . $reason])->filter()->implode(' | '));
            }

            // log the outcome
            $adminUser = auth()->user();
            ActivityLog::create([
                'asset_id' => $asset?->id,
                'Employee_ID' => $adminUser->id ?? null,
                'user_name' => $adminUser->name ?? 'System',
                'action' => $lower === 'accepted' ? 'Return Accepted' : 'Return Rejected',
                'target_type' => $asset ? 'Asset' : 'Mixed Items',
                'target_name' => $asset ? $asset->Asset_Name : 'Mixed Items',
                'details' => "Return request was {$lower}" . ($reason ? ": {$reason}" : ''),
            ]);

            // let the original requester know of the decision
            $requester = User::find($request->Sender_ID);
            if ($requester && $requester->email) {
                $statusLabel = ucfirst($lower === 'closed' ? 'accepted' : $lower);
                Mail::to($requester->email)->send(new GeneralOperationalMail(
                    $requester,
                    "Return Request {$statusLabel}",
                    'Request Update',
                    "Your return request has been {$statusLabel} by the administration.",
                    [
                        ['label' => 'Asset', 'value' => $asset?->Asset_Name ?? 'Items'],
                        ['label' => 'Decision', 'value' => $statusLabel],
                        ['label' => 'Reason', 'value' => $reason ?? 'No additional notes provided.'],
                    ],
                    'Go to Workspace',
                    config('app.url') . '/dashboard/user/workspace'
                ));
            }

            // Requirement 2: Notify All Admins/Superadmins
            $allAdmins = User::whereIn('role', ['admin', 'superadmin'])->get()->filter(fn($u) => $u->email);
            foreach ($allAdmins as $adm) {
                $statusLabel = ucfirst($lower === 'closed' ? 'accepted' : $lower);
                Mail::to($adm->email)->send(new GeneralOperationalMail(
                    $adm,
                    "Return Action Confirmed",
                    'Action Logged',
                    "The return request from {$requester->name} has been marked as {$statusLabel}.",
                    [
                        ['label' => 'Staff Member', 'value' => $requester->name],
                        ['label' => 'Equipment', 'value' => $asset?->Asset_Name ?? 'Multiple Items'],
                    ],
                    'View Movements',
                    config('app.url') . '/dashboard/admin/movements'
                ));
            }
            // determine final workflow status
            if ($lower === 'accepted') {
                $lower = 'closed';
            }
        }

        // Requirement 9: Status lifecycle - Revert to Deployed if rejected
        if ($lower === 'rejected' && $request->asset) {
            $request->asset->update([
                'Status_ID' => Status::whereIn('Status_Name', ['Deployed', 'Assigned', 'In Use'])->value('id') ?? $request->asset->Status_ID
            ]);
        }


        if ($reason) {
            $request->reason = $reason;
        }

        $request->update(['Workflow_Status' => $lower]);

        return $request->fresh(['asset.status', 'sender', 'actionedBy']);
    }

    /**
     * Helper to process a returned item for a user's pivot table.
     */
    private function returnItemToStock($relation, string $foreignKey, int $itemId): void
    {
        $pivot = $relation->where($foreignKey, $itemId)->wherePivotNull('returned_at')->first();
        if ($pivot) {
            $qty = $pivot->pivot->quantity ?? 1;
            if ($qty > 1) {
                $relation->updateExistingPivot($itemId, ['quantity' => $qty - 1]);
            } else {
                $relation->updateExistingPivot($itemId, ['returned_at' => now()]);
            }
        }
    }

    public function completeInspection(int $id, int $adminId, array $data): ReturnRequest
    {
        $request = ReturnRequest::findOrFail($id);

        // build a combined notes string for audit
        $notes = trim(collect([
            $request->Notes,
            !empty($data['admin_notes']) ? ('Admin notes: ' . $data['admin_notes']) : null,
            'Disposition: ' . str_replace('_', ' ', $data['disposition'] ?? ''),
        ])->filter()->implode(' | '));

        DB::transaction(function () use ($request, $adminId, $data, $notes) {
            $request->update([
                'Workflow_Status' => 'inspected',
                'Admin_Condition' => $data['condition'],
                'Missing_Items' => $data['missing_items'] ?? [],
                'Notes' => $notes,
                'Actioned_By' => $adminId,
                'Actioned_At' => now(),
            ]);

            $assetObj = $request->asset;
            ActivityLog::create([
                'asset_id' => $assetObj?->id,
                'Employee_ID' => $adminId,
                'user_name' => auth()->user()->name ?? 'Admin',
                'action' => 'Return Inspection',
                'target_type' => $assetObj ? 'Asset' : 'Mixed Items',
                'target_name' => $assetObj ? $assetObj->Asset_Name : 'Mixed Items',
                'details' => 'Asset inspected; awaiting approval or rejection',
            ]);
        });

        // notify the requester that their item has been inspected and is now
        // awaiting final decision
        $requester = User::find($request->Sender_ID);
        if ($requester && $requester->email) {
            Mail::to($requester->email)->send(new GeneralOperationalMail(
                $requester,
                'Return Inspected',
                'Under Review',
                'Your return request has been physically inspected by an administrator. A final decision will be communicated shortly.',
                [
                    ['label' => 'Asset', 'value' => $request->asset?->Asset_Name ?? 'Items'],
                    ['label' => 'Admin Condition', 'value' => $data['condition'] ?? 'Good'],
                    ['label' => 'Admin Notes', 'value' => $data['admin_notes'] ?? 'None'],
                ],
                'Track Status',
                config('app.url') . '/dashboard/user/workspace'
            ));
        }

        return $request->fresh(['asset.status', 'sender', 'actionedBy']);
    }

    /**
     * Resolve a human-readable name for an item attached to a return request.
     * This keeps the frontend simple; it just renders the string rather than
     * having to fetch each model itself.
     */
    private function resolveItemName(array $item): string
    {
        $type = $item['type'] ?? '';
        $id = $item['id'] ?? null;
        if (!$id) {
            return ucfirst($type ?: 'unknown');
        }

        switch ($type) {

            case 'accessory':
                return Accessory::find($id)?->name
                    ?? "Accessory";
            case 'license':
                return License::find($id)?->name
                    ?? "License";
            case 'consumable':
                // consumable uses item_name rather than name
                return Consumable::find($id)?->item_name
                    ?? "Consumable";
            default:
                return ucfirst($type);
        }
    }

    /**
     * Helper for converting a model instance into the API-friendly array
     * used by the frontend.  This lives in the service so it can be shared
     * between multiple controllers.
     */
    public function mapReturnRequest(ReturnRequest $r): array
    {
        // ensure items array includes a human-readable name so the front end can
        // display each returned accessory/component/etc on its own line.
        $items = collect($r->Items ?? [])->map(function ($i) {
            return [
                'type' => $i['type'] ?? 'unknown',
                'id' => $i['id'] ?? null,
                'name' => $this->resolveItemName($i),
            ];
        })->toArray();

        return [
            'id' => $r->id,
            'type' => 'return',
            'status' => strtolower($r->Workflow_Status ?? 'pending'),
            'sender_condition' => $r->Sender_Condition,
            'admin_condition' => $r->Admin_Condition,
            'missing_items' => $r->Missing_Items ?? [],
            'items' => $items,
            'notes' => $r->Notes,
            'reason' => $r->reason,
            'sender' => $r->sender ? ['id' => $r->sender->id, 'name' => $r->sender->name] : null,
            'receiver' => null,
            'admin' => $r->actionedBy ? ['id' => $r->actionedBy->id, 'name' => $r->actionedBy->name] : null,
            'asset' => $r->asset ? [
                'id' => $r->asset->id,
                'model' => $r->asset->Asset_Name,
                'serial' => $r->asset->Serial_No,
                'asset_tag' => 'AST-' . str_pad((string) $r->asset->id, 4, '0', STR_PAD_LEFT),
                'status_name' => optional($r->asset->status)->Status_Name,
            ] : null,
            'created_at' => $r->created_at?->toIso8601String(),
        ];
    }
}
