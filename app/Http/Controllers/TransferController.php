<?php

namespace App\Http\Controllers;

use App\Mail\AssetAssigned;
use App\Mail\TransferUpdate;
use App\Models\Accessory;
use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\License;
use App\Models\Maintenance;
use App\Models\Status;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transfer::with(['asset.status', 'sender', 'receiver', 'actionedBy']);

        if ($request->boolean('pending')) {
            // hide items that have been closed or rejected – everything else is
            // still actionable (pending_inspection, inspected, approved, etc.)
            $query->whereNotIn('Workflow_Status', ['closed', 'rejected']);
        }

        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $paginated = $query->latest()->paginate($perPage);

        // map results using the shared helper so the shape stays
        // consistent with other endpoints
        $paginated->getCollection()->transform(fn ($t) => $this->mapTransfer($t));

        return response()->json($paginated);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->storeReturnRequest($request);
    }

    public function getUserTransfers(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $query = Transfer::with(['asset.status', 'sender', 'receiver', 'actionedBy'])
            ->where(function ($q) use ($userId) {
                $q->where('Sender_ID', $userId)
                    ->orWhere('Receiver_ID', $userId)
                    ->orWhere('Employee_ID', $userId);
            });

        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $paginated = $query->latest()->paginate($perPage);

        // map results using the shared helper so the shape stays
        // consistent with other endpoints
        $paginated->getCollection()->transform(fn ($t) => $this->mapTransfer($t));

        return response()->json($paginated);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['status' => 'required|string|max:60']);

        $transfer = Transfer::findOrFail($id);
        $transfer->update(['Workflow_Status' => strtolower($data['status'])]);

        return response()->json(['message' => 'Transfer status updated.', 'transfer' => $this->mapTransfer($transfer->fresh(['asset.status', 'sender', 'receiver', 'actionedBy']))]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $transfer = Transfer::findOrFail($id);
        $user = $request->user();

        $isAdmin = (($user->role ?? 'user') === 'admin');
        $isOwnerPending = ((int) $transfer->Sender_ID === (int) $user->id)
            && in_array(strtolower($transfer->Workflow_Status ?? ''), ['pending', 'pending_inspection'], true);

        if (! $isAdmin && ! $isOwnerPending) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transfer->delete();

        return response()->json(['message' => 'Transfer deleted successfully.']);
    }

    public function getMyAssets(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json([]);
        }

        $query = Asset::query();

        // Admin can see all available assets for assignment
        if ($user->role === 'admin') {
            $query->where(function ($q) {
                $q->whereNull('Employee_ID')
                    ->orWhereHas('status', fn ($s) => $s->whereIn('Status_Name', ['Available', 'Ready to Deploy']));
            });
        } else {
            // Staff and HOD see only their assigned assets
            $query->where('Employee_ID', $user->id);
        }

        $assets = $query->with(['status', 'category', 'locationModel'])->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'model' => $a->Asset_Name,
                'serial' => $a->Serial_No,
                'barcode' => $a->barcode,
                'category' => $a->category?->name ?? $a->Asset_Category,
                'location' => $a->locationModel?->name ?? $a->Location,
                'status' => $a->status?->Status_Name ?? 'Assigned',
            ]);

        return response()->json($assets);
    }

    public function storeReturnRequest(Request $request): JsonResponse
    {
        // If this specific route is hit, default the type to 'return' if not provided.
        // This makes the endpoint more intuitive and robust against frontend omissions.
        if ($request->is('api/transfers/return') && ! $request->has('type')) {
            $request->merge(['type' => 'return']);
        }

        $data = $request->validate([
            'asset_id' => 'nullable|integer|exists:assets,id',
            'items' => 'nullable|array',
            'items.*.type' => 'required_with:items|string|in:asset,accessory,license,consumable',
            'items.*.id' => 'required_with:items|integer',
            'type' => 'required|in:return,transfer',
            'receiver_id' => 'nullable|integer|exists:users,id',
            'sender_condition' => 'nullable|string|max:255',
            'missing_items' => 'nullable|array',
            'missing_items.*' => 'string|max:255',
            'issue_notes' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'reason' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        $asset = null;
        if (! empty($data['asset_id'])) {
            $asset = Asset::findOrFail($data['asset_id']);
            if ((int) $asset->Employee_ID !== (int) $user->id) {
                return response()->json(['message' => 'You can only request transfer for assets assigned to you.'], 403);
            }
        }

        // A request must contain either a primary asset or at least one other item.
        if (empty($data['asset_id']) && empty($data['items'])) {
            return response()->json(['message' => 'You must select an asset or at least one component/accessory to transfer/return.'], 422);
        }

        if ($data['type'] === 'transfer' && empty($data['receiver_id'])) {
            return response()->json(['message' => 'Receiver is required for transfer type.'], 422);
        }

        $statusId = Status::firstOf(['Pending', 'Requested'])
                    ?? $asset?->Status_ID
                    ?? Status::first()?->id
                    ?? 1;

        // create Included_Items for user‑initiated transfer/return
        $included = [];
        if (! empty($data['items']) && is_array($data['items'])) {
            $included = array_map(fn ($i) => $this->resolveItemName((array) $i), $data['items']);
        }

        $transfer = Transfer::create([
            'Asset_ID' => $asset?->id,
            'Employee_ID' => $user->id,
            'Sender_ID' => $user->id,
            'Receiver_ID' => $data['receiver_id'] ?? null,
            'Status_ID' => $statusId,
            'Transfer_Date' => now(),
            'Type' => $data['type'],
            'Workflow_Status' => 'pending_inspection',
            'Sender_Condition' => $data['sender_condition'] ?? 'Good',
            'Missing_Items' => $data['missing_items'] ?? [],
            'Included_Items' => $included,
            'Items' => $data['items'] ?? [],
            'Notes' => trim(collect([
                $data['notes'] ?? null,
                ! empty($data['issue_notes']) ? ('Reported issue: '.$data['issue_notes']) : null,
            ])->filter()->implode(' | ')) ?: null,
            'reason' => $data['reason'] ?? null,
        ]);

        ActivityLog::create([
            'asset_id' => $asset?->id,
            'Employee_ID' => $user->id,
            'user_name' => $user->name,
            'action' => 'Transfer Requested',
            'target_type' => $asset ? 'Asset' : 'Mixed Items',
            'target_name' => $asset ? $asset->Asset_Name : 'Mixed Items',
            'details' => 'Transfer request submitted for admin inspection'.
                (! empty($data['items']) ? ' (items: '.collect($data['items'])->pluck('type')->implode(', ').')' : ''),
        ]);

        // notify the requesting user that their transfer/return has been received
        if ($user->email) {
            Mail::to($user->email)->send(new TransferUpdate($transfer, $user, 'request_received'));
        }

        // Notify admins about the new request
        $admins = User::where('role', 'admin')->get()->filter(fn ($u) => $u->email);
        if ($admins->isNotEmpty()) {
            $subject = 'New Asset Transfer/Return Request';
            $details = "A new request has been submitted and is pending inspection.\n\n";

            if ($data['type'] === 'return') {
                $subject = 'New Asset Return Request';
                $details .= "User: {$user->name}\n";
                $details .= $asset ? "Asset: {$asset->Asset_Name}\n" : 'Items: '.collect($data['items'] ?? [])->pluck('type')->implode(', ')."\n";
                $details .= 'Type: Return';
            } elseif ($data['type'] === 'transfer') {
                $receiver = User::find($data['receiver_id']);
                $subject = 'New Asset Transfer Request';
                $details .= "Sender: {$user->name}\nReceiver: {$receiver->name}\n";
                if ($asset) {
                    $details .= "Asset: {$asset->Asset_Name}\n";
                } else {
                    // list each extra with its friendly name
                    $names = collect($data['items'] ?? [])->map(fn ($i) => $this->resolveItemName((array) $i));
                    $details .= 'Items: '.$names->implode(', ')."\n";
                }
                $details .= 'Type: Transfer';
            }

            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new TransferUpdate($transfer, $admin, 'request_received', $subject));
            }
        }

        return response()->json(['message' => 'Transfer request submitted.', 'transfer' => $this->mapTransfer($transfer->fresh(['asset.status', 'sender', 'receiver', 'actionedBy']))], 201);
    }

    public function indexPending(Request $request): JsonResponse
    {
        $query = Transfer::with(['asset.status', 'sender', 'receiver'])
            ->where('Workflow_Status', 'pending_inspection');

        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $paginated = $query->latest()->paginate($perPage);

        // map results using the shared helper so the shape stays
        // consistent with other endpoints
        $paginated->getCollection()->transform(fn ($t) => $this->mapTransfer($t));

        return response()->json($paginated);
    }

    public function completeInspection(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'condition' => 'required|string|max:255',
            'disposition' => 'required|in:ready_to_deploy,non_deployable,maintenance',
            'missing_items' => 'nullable|array',
            'missing_items.*' => 'string|max:255',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $transfer = Transfer::with('asset', 'sender', 'receiver')->findOrFail($id);
        $asset = $transfer->asset;

        DB::transaction(function () use ($transfer, $asset, $data, $request) {
            $statusCandidates = match ($data['disposition']) {
                'ready_to_deploy' => ['Ready to Deploy', 'Available'],
                'non_deployable' => ['Non-Deployable', 'Archived/Lost', 'Retired'],
                'maintenance' => ['Out for Repair', 'Maintenance', 'Pending'],
            };

            // After inspection, if there is a primary asset we unassign it
            // and update its status based on the disposition.  Mixed‑item requests
            // don't have an asset row, so $asset may be null.
            if ($asset) {
                $asset->update([
                    'Employee_ID' => null,
                    'Status_ID' => Status::firstOf($statusCandidates) ?? $asset->Status_ID,
                ]);
            }

            $next = ($transfer->Type === 'transfer' && $transfer->Receiver_ID)
                ? 'pending_verification'
                : 'completed';

            // If the transfer is a "return" and is now completed, process the items.
            if ($next === 'completed' && ! empty($transfer->Items) && is_array($transfer->Items)) {
                $sender = $transfer->sender;
                if ($sender) {
                    foreach ($transfer->Items as $itm) {
                        $type = $itm['type'] ?? null;
                        $id = $itm['id'] ?? null;
                        if (! $type || ! $id) {
                            continue;
                        }

                        switch ($type) {

                            case 'accessory':
                                $item = Accessory::find($id);
                                if ($item) {
                                    $this->returnItemToStock($sender->accessories(), 'accessory_id', $item->id);
                                    $item->increment('remaining_qty');
                                }
                                break;
                            case 'consumable':
                                $item = Consumable::find($id);
                                if ($item) {
                                    $this->returnItemToStock($sender->consumables(), 'consumable_id', $item->id);
                                    $item->increment('in_stock');
                                }
                                break;
                            case 'license':
                                $item = License::find($id);
                                if ($item) {
                                    $pivot = $sender->licenses()->where('license_id', $item->id)->wherePivotNull('returned_at')->first();
                                    if ($pivot) {
                                        $sender->licenses()->updateExistingPivot($item->id, ['returned_at' => now()]);
                                    }
                                    $item->increment('remaining_seats');
                                }
                                break;
                        }
                    }
                }
            }

            $transfer->update([
                'Workflow_Status' => $next,
                'Admin_Condition' => $data['condition'],
                'Missing_Items' => $data['missing_items'] ?? [],
                'Notes' => trim(collect([
                    $transfer->Notes,
                    ! empty($data['admin_notes']) ? ('Admin notes: '.$data['admin_notes']) : null,
                    'Disposition: '.str_replace('_', ' ', $data['disposition']),
                ])->filter()->implode(' | ')),
                'Actioned_By' => $request->user()->id,
                'Actioned_At' => now(),
            ]);

            if ($data['disposition'] === 'maintenance' && $asset) {
                Maintenance::create([
                    'Asset_ID' => $asset->id,
                    'Ticket_ID' => null,
                    'Request_Date' => now(),
                    'Completion_Date' => null,
                    'Maintenance_Type' => 'Return Inspection - Repair Needed',
                    'Description' => $data['admin_notes'] ?? 'Flagged during return inspection.',
                    'Cost' => null,
                    'Status_ID' => Status::firstOf(['Out for Repair', 'Maintenance', 'Pending']) ?? 1,
                    'Maintenance_Date' => now(),
                ]);
            }

            // If it's a transfer, notify the receiver it's ready for verification
            if ($next === 'pending_verification' && $transfer->receiver) {
                $receiver = $transfer->receiver;
                $sender = $transfer->sender;
                $admin = $request->user();

                // build subject/details more generally in case there is no asset
                if ($asset) {
                    $subject = "Asset Ready for Verification";
                    $details = "An asset, '{$asset->Asset_Name}', originally from {$sender->name}, has been processed by {$admin->name} and is now assigned to you. Please log in to your dashboard to verify the inbound transfer.";
                } else {
                    $subject = 'Items Ready for Verification';
                    $details = "A set of items requested by {$sender->name} has been processed by {$admin->name} and is now awaiting your verification. Please log in to review the transfer details.";
                }

                Mail::to($receiver->email)->send(new TransferUpdate($transfer, $receiver, 'ready_for_verification'));

                // also tell the sender their request was inspected
                if ($sender && $sender->email) {
                    Mail::to($sender->email)->send(new TransferUpdate($transfer, $sender, 'inspection_completed'));
                }
            }
        });

        return response()->json(['message' => 'Inspection completed.', 'transfer' => $this->mapTransfer($transfer->fresh(['asset.status', 'sender', 'receiver', 'actionedBy']))]);
    }

    public function assignToUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => 'nullable|integer|exists:assets,id',
            'items' => 'nullable|array',
            'items.*.type' => 'required_with:items|string|in:asset,accessory,license', // Removed consumable
            'items.*.id' => 'required_with:items|integer',
            'receiver_id' => 'required|integer|exists:users,id',
            'notes' => 'nullable|string|max:2000',
            'direct' => 'nullable|boolean',
        ]);

        $asset = null;
        if (! empty($data['asset_id'])) {
            $asset = Asset::findOrFail($data['asset_id']);
        }
        $admin = $request->user();
        $receiver = User::findOrFail($data['receiver_id']);
        $direct = $request->boolean('direct', false);

        // Filter out any consumables just in case
        $filteredItems = collect($data['items'] ?? [])
            ->filter(fn ($i) => ($i['type'] ?? '') !== 'consumable')
            ->toArray();

        // figure out a human‑readable included items list
        $included = ['Charger'];
        if (! empty($filteredItems)) {
            $included = array_map(function ($i) {
                return $this->resolveItemName((array) $i);
            }, $filteredItems);
        }

        $workflowStatus = $direct ? 'deployed' : 'pending_verification';

        $transfer = Transfer::create([
            'Asset_ID' => $asset?->id,
            'Employee_ID' => $data['receiver_id'],
            'Sender_ID' => Auth::id(),
            'Receiver_ID' => $data['receiver_id'],
            'Status_ID' => $direct ? (Status::firstOf(['Deployed', 'Assigned']) ?? 2) : (Status::firstOf(['Pending', 'Requested']) ?? 1),
            'Transfer_Date' => now(),
            'Type' => 'assignment',
            'Workflow_Status' => $workflowStatus,
            'Admin_Condition' => 'Good',
            'Included_Items' => $included,
            'Items' => $filteredItems,
            'Notes' => $data['notes'] ?? 'Assigned by admin',
            'Actioned_By' => $admin->id,
            'Actioned_At' => now(),
        ]);

        if ($direct) {
            // Direct assignment: move custody immediately
            if ($asset) {
                $asset->update([
                    'Employee_ID' => $receiver->id,
                    'Status_ID' => Status::firstOf(['Deployed', 'Assigned', 'In Use']) ?? $asset->Status_ID,
                ]);

                ActivityLog::create([
                    'asset_id' => $asset->id,
                    'Employee_ID' => $admin->id,
                    'user_name' => $admin->name,
                    'action' => 'Direct Assignment',
                    'target_type' => 'Asset',
                    'target_name' => $asset->Asset_Name,
                    'details' => "Directly assigned to {$receiver->name} by admin",
                ]);
            }

            // Sync extra items
            foreach ($filteredItems as $itm) {
                $type = $itm['type'] ?? null;
                $id = $itm['id'] ?? null;
                if (! $type || ! $id) {
                    continue;
                }
                switch ($type) {

                    case 'accessory':
                        $acc = Accessory::find($id);
                        if ($acc && $acc->remaining_qty > 1) {
                            $receiver->accessories()->attach($acc->id, ['quantity' => 1]);
                        }
                        break;
                    case 'license':
                        $lic = License::find($id);
                        if ($lic) {
                            $receiver->licenses()->attach($lic->id);
                        }
                        break;
                }
            }
        } else {
            // Traditional flow: keep admin custody until receiver verifies
            if ($asset) {
                $asset->update([
                    'Employee_ID' => $admin->id,
                    'Status_ID' => Status::firstOf(['Pending', 'Ready to Deploy', 'Available']) ?? $asset->Status_ID,
                ]);
            }
        }

        // Notify user about the new assignment
        if ($receiver->email) {
            Mail::to($receiver->email)->send(new AssetAssigned($asset, $receiver, $admin));
        }

        return response()->json([
            'message' => $direct ? 'Asset assigned successfully.' : 'Assignment created, awaiting staff verification.',
            'transfer' => $this->mapTransfer($transfer->fresh(['asset.status', 'sender', 'receiver', 'actionedBy'])),
        ], 201);
    }

    public function getPendingAssignments(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Transfer::with(['asset', 'actionedBy', 'sender'])
            ->where('Receiver_ID', $user->id)
            ->where('Workflow_Status', 'pending_verification');

        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $paginated = $query->latest()->paginate($perPage);

        // map results using the shared helper so the shape stays
        // consistent with other endpoints
        $paginated->getCollection()->transform(fn ($t) => $this->mapTransfer($t));

        return response()->json($paginated);
    }

    public function verifyInbound(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:accepted,disputed',
            'notes' => 'nullable|string|max:2000',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $receiver = $request->user();
            $transfer = Transfer::with('asset', 'sender', 'actionedBy')
                ->where('Receiver_ID', $receiver->id)
                ->findOrFail($id);

            $asset = $transfer->asset;
            $admin = $transfer->actionedBy;

            if ($request->status === 'accepted') {
                // notify sender/admin – omit asset name if missing
                $sender = $transfer->sender;
                if ($sender && $sender->email) {
                    Mail::to($sender->email)->send(new TransferUpdate($transfer, $sender, 'inspection_completed', 'Transfer verified and completed'));
                }

                $transfer->update([
                    'Workflow_Status' => 'deployed',
                    'Actioned_At' => now(),
                ]);

                // relocate primary asset if present
                if ($asset) {
                    $asset->update([
                        'Employee_ID' => $receiver->id,
                        'Status_ID' => Status::firstOf(['Deployed', 'Assigned', 'In Use']) ?? $asset->Status_ID,
                    ]);

                    ActivityLog::create([
                        'asset_id' => $asset->id,
                        'Employee_ID' => $receiver->id,
                        'user_name' => $receiver->name,
                        'action' => 'Transfer Verified',
                        'target_type' => 'Asset',
                        'target_name' => $asset->Asset_Name,
                        'details' => "Transfer verified by {$receiver->name}",
                    ]);
                } else {
                    ActivityLog::create([
                        'Employee_ID' => $receiver->id,
                        'user_name' => $receiver->name,
                        'action' => 'Transfer Verified',
                        'target_type' => 'Mixed Items',
                        'target_name' => 'Mixed Items',
                        'details' => "Transfer verified by {$receiver->name}",
                    ]);
                }

                // handle any extra items bundled with the transfer
                if (! empty($transfer->Items) && is_array($transfer->Items)) {
                    foreach ($transfer->Items as $itm) {
                        $type = $itm['type'] ?? null;
                        $id = $itm['id'] ?? null;
                        if (! $type || ! $id) {
                            continue;
                        }
                        switch ($type) {

                            case 'accessory':
                                $acc = Accessory::find($id);
                                if ($acc && $acc->remaining_qty > 0) {
                                    if ($sender) {
                                        $pivot = $sender->accessories()->where('accessory_id', $acc->id)
                                            ->wherePivotNull('returned_at')
                                            ->first();
                                        if ($pivot) {
                                            $qty = $pivot->pivot->quantity;
                                            if ($qty > 1) {
                                                $sender->accessories()->updateExistingPivot($acc->id, ['quantity' => $qty - 1]);
                                            } else {
                                                $sender->accessories()->updateExistingPivot($acc->id, ['returned_at' => now()]);
                                            }
                                        }
                                    }
                                    $receiver->accessories()->attach($acc->id, ['quantity' => 1]);
                                }
                                break;
                            case 'consumable':
                                $cons = Consumable::find($id);
                                if ($cons && $cons->in_stock > 0) {
                                    if ($sender) {
                                        $pivot = $sender->consumables()->where('consumable_id', $cons->id)
                                            ->wherePivotNull('returned_at')
                                            ->first();
                                        if ($pivot) {
                                            $qty = $pivot->pivot->quantity;
                                            if ($qty > 1) {
                                                $sender->consumables()->updateExistingPivot($cons->id, ['quantity' => $qty - 1]);
                                            } else {
                                                $sender->consumables()->updateExistingPivot($cons->id, ['returned_at' => now()]);
                                            }
                                        }
                                    }
                                    $receiver->consumables()->attach($cons->id, ['quantity' => 1]);
                                }
                                break;
                            case 'license':
                                $lic = License::find($id);
                                if ($lic) {
                                    if ($sender) {
                                        $sender->licenses()->updateExistingPivot($lic->id, ['returned_at' => now()]);
                                    }
                                    $receiver->licenses()->attach($lic->id);
                                }
                                break;
                        }
                    }
                }

                return response()->json(['message' => 'Asset verified and assigned to you.']);
            }

            // otherwise we assume a dispute
            $transfer->update([
                'Workflow_Status' => 'disputed',
                'Notes' => $request->notes ?? 'User reported discrepancy during inbound verification.',
            ]);

            ActivityLog::create([
                'asset_id' => $asset?->id,
                'Employee_ID' => $receiver->id,
                'user_name' => $receiver->name,
                'action' => 'Transfer Disputed',
                'target_type' => $asset ? 'Asset' : 'Mixed Items',
                'target_name' => $asset ? $asset->Asset_Name : 'Mixed Items',
                'details' => 'Transfer disputed'.($request->notes ? ": {$request->notes}" : ''),
            ]);

            if ($asset) {
                $asset->update([
                    'Employee_ID' => $transfer->Actioned_By,
                    'Status_ID' => Status::firstOf(['Ready to Deploy', 'Available']) ?? $asset->Status_ID,
                ]);
            }

            // Notify admins of dispute
            $admins = User::where('role', 'admin')->get()->filter(fn ($u) => $u->email);
            if ($admins->isNotEmpty()) {
                $subject = "Asset Transfer Disputed: #{$transfer->id}";
                $details = "{$receiver->name} has disputed the inbound verification for asset '{$asset->Asset_Name}'.\n\nReason: ".($request->notes ?? 'No reason provided.')."\n\nPlease review the transfer record #{$transfer->id}.";

                foreach ($admins as $admin) {
                    Mail::to($admin->email)->send(new TransferUpdate($transfer, $admin, 'disputed'));
                }
            }

            return response()->json(['message' => 'Discrepancy reported to admin.'], 422);
        });
    }

    /**
     * Helper to process a returned item for a user.
     * It finds the user's assignment of the item and either decrements the quantity
     * or marks the entire assignment as returned.
     */
    private function returnItemToStock($relation, string $foreignKey, int $itemId): void
    {
        $pivot = $relation->where($foreignKey, $itemId)->wherePivotNull('returned_at')->first();
        if ($pivot) {
            $qty = $pivot->pivot->quantity;
            if ($qty > 1) {
                // If they have more than one, decrement the pivot quantity
                $relation->updateExistingPivot($itemId, ['quantity' => $qty - 1]);
            } else {
                // If they have one, mark it as returned
                $relation->updateExistingPivot($itemId, ['returned_at' => now()]);
            }
        }
    }

    /**
     * Return a displayable name for an item entry from the transfers table.
     * Mirrors the logic in ReturnRequestService so both APIs behave the same.
     */
    /**
     * Return a display name for an item entry from the transfers table.
     * Mirrors the logic in ReturnRequestService so both APIs behave the same.
     */
    private function resolveItemName(array $item): string
    {
        $type = $item['type'] ?? '';
        $id = $item['id'] ?? null;
        if (! $id) {
            return ucfirst($type ?: 'unknown');
        }

        switch ($type) {

            case 'accessory':
                return Accessory::find($id)?->name
                    ?? "Accessory #{$id}";
            case 'license':
                return License::find($id)?->name
                    ?? "License #{$id}";
            case 'consumable':
                return Consumable::find($id)?->item_name
                    ?? "Consumable #{$id}";
            default:
                return ucfirst($type)." #{$id}";
        }
    }

    /**
     * Turn a raw item array into a richer structure suitable for the UI.
     * Provides name, type and, when possible, a small set of details taken
     * from the underlying database record (serial, category, quantity, etc.).
     */
    private function formatItem(array $item): array
    {
        $type = $item['type'] ?? 'unknown';
        $id = $item['id'] ?? null;
        $result = [
            'type' => $type,
            'id' => $id,
            'name' => $this->resolveItemName($item),
            'details' => null,
        ];

        if (! $id) {
            return $result;
        }

        switch ($type) {

            case 'accessory':
                $m = Accessory::find($id);
                if ($m) {
                    $result['details'] = $m->only(['id', 'name', 'category', 'model_number', 'remaining_qty']);
                }
                break;
            case 'consumable':
                $m = Consumable::find($id);
                if ($m) {
                    $result['details'] = $m->only(['id', 'item_name', 'in_stock']);
                }
                break;
            case 'license':
                $m = License::find($id);
                if ($m) {
                    $result['details'] = $m->only(['id', 'name']);
                }
                break;
        }

        return $result;
    }

    private function mapTransfer(Transfer $t): array
    {
        $items = collect($t->Items ?? [])->map(fn ($i) => $this->formatItem((array) $i))->toArray();

        return [
            'id' => $t->id,
            'type' => strtolower($t->Type ?? 'transfer'),
            'status' => strtolower($t->Workflow_Status ?? 'pending'),
            'sender_condition' => $t->Sender_Condition,
            'admin_condition' => $t->Admin_Condition,
            'included_items' => $t->Included_Items ?? [],
            'missing_items' => $t->Missing_Items ?? [],
            'items' => $items,
            'notes' => $t->Notes,
            'reason' => $t->reason,
            'sender' => $t->sender ? ['id' => $t->sender->id, 'name' => $t->sender->name] : null,
            'receiver' => $t->receiver ? ['id' => $t->receiver->id, 'name' => $t->receiver->name] : null,
            'admin' => $t->actionedBy ? ['id' => $t->actionedBy->id, 'name' => $t->actionedBy->name] : null,
            'asset' => $t->asset ? [
                'id' => $t->asset->id,
                'model' => $t->asset->Asset_Name,
                'serial' => $t->asset->Serial_No,
                'asset_tag' => 'AST-'.str_pad((string) $t->asset->id, 4, '0', STR_PAD_LEFT),
                'status_name' => optional($t->asset->status)->Status_Name,
            ] : null,
            'created_at' => $t->created_at,
        ];
    }
}


