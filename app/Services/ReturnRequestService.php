<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\ReturnRequest;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReturnRequestService
{
    public function listAll()
    {
        return ReturnRequest::with(['asset.status', 'sender', 'actionedBy'])
            ->latest()
            ->get()
            ->map(fn ($r) => $this->mapReturnRequest($r));
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
            ->map(fn ($r) => $this->mapReturnRequest($r));
    }

    public function getMyAssets(int $userId)
    {
        return Asset::where('Employee_ID', $userId)
            ->get(['id', 'Asset_Name', 'Serial_No'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'model' => $a->Asset_Name,
                'serial' => $a->Serial_No,
            ]);
    }

    public function createRequest(User $user, array $data): ReturnRequest
    {
        $asset = Asset::findOrFail($data['asset_id']);

        if ((int) $asset->Employee_ID !== (int) $user->id) {
            abort(response()->json(['message' => 'You can only request return for assets assigned to you.'], 403));
        }

        $returnRequest = ReturnRequest::create([
            'Asset_ID' => $asset->id,
            'Employee_ID' => $user->id,
            'Sender_ID' => $user->id,
            'Status_ID' => $this->statusId(['Pending', 'Requested']) ?? $asset->Status_ID,
            'Request_Date' => now(),
            'Workflow_Status' => 'pending_inspection',
            'Sender_Condition' => $data['sender_condition'] ?? 'Good',
            'Missing_Items' => $data['missing_items'] ?? [],
            'Notes' => trim(collect([
                $data['notes'] ?? null,
                !empty($data['issue_notes']) ? ('Reported issue: ' . $data['issue_notes']) : null,
            ])->filter()->implode(' | ')) ?: null,
        ]);

        ActivityLog::create([
            'Employee_ID' => $user->id,
            'user_name' => $user->name,
            'action' => 'Return Requested',
            'target_type' => 'Asset',
            'target_name' => $asset->Asset_Name,
            'details' => "Return Request #{$returnRequest->id} submitted for admin inspection",
        ]);

        return $returnRequest->fresh(['asset.status', 'sender', 'actionedBy']);
    }

    public function updateStatus(int $id, string $status): ReturnRequest
    {
        $request = ReturnRequest::findOrFail($id);
        $request->update(['Workflow_Status' => strtolower($status)]);

        return $request->fresh(['asset.status', 'sender', 'actionedBy']);
    }

    public function completeInspection(int $id, int $adminId, array $data): ReturnRequest
    {
        $request = ReturnRequest::with('asset')->findOrFail($id);
        $asset = $request->asset;

        DB::transaction(function () use ($request, $asset, $data, $adminId) {
            $statusCandidates = match ($data['disposition']) {
                'ready_to_deploy' => ['Ready to Deploy', 'Available'],
                'non_deployable' => ['Non-Deployable', 'Archived/Lost', 'Retired'],
                'maintenance' => ['Out for Repair', 'Maintenance', 'Pending'],
            };

            $asset->update([
                'Employee_ID' => $adminId,
                'Status_ID' => $this->statusId($statusCandidates) ?? $asset->Status_ID,
            ]);

            $request->update([
                'Workflow_Status' => 'inspected',
                'Admin_Condition' => $data['condition'],
                'Missing_Items' => $data['missing_items'] ?? [],
                'Notes' => trim(collect([
                    $request->Notes,
                    !empty($data['admin_notes']) ? ('Admin notes: ' . $data['admin_notes']) : null,
                    'Disposition: ' . str_replace('_', ' ', $data['disposition']),
                ])->filter()->implode(' | ')),
                'Actioned_By' => $adminId,
                'Actioned_At' => now(),
            ]);

            if ($data['disposition'] === 'maintenance') {
                Maintenance::create([
                    'Asset_ID' => $asset->id,
                    'Ticket_ID' => null,
                    'Request_Date' => now(),
                    'Completion_Date' => null,
                    'Maintenance_Type' => 'Return Inspection - Repair Needed',
                    'Description' => $data['admin_notes'] ?? 'Flagged during return inspection.',
                    'Cost' => null,
                    'Status_ID' => $this->statusId(['Out for Repair', 'Maintenance', 'Pending']) ?? 1,
                    'Maintenance_Date' => now(),
                ]);
            }
        });

        return $request->fresh(['asset.status', 'sender', 'actionedBy']);
    }

    public function destroy(int $id, User $user): void
    {
        $request = ReturnRequest::findOrFail($id);

        $isAdmin = (($user->role ?? 'user') === 'admin');
        $isOwnerPending = ((int) $request->Sender_ID === (int) $user->id)
            && in_array(strtolower($request->Workflow_Status ?? ''), ['pending', 'pending_inspection'], true);

        if (!$isAdmin && !$isOwnerPending) {
            abort(response()->json(['message' => 'Forbidden'], 403));
        }

        $request->delete();
    }

    public function mapReturnRequest(ReturnRequest $r): array
    {
        return [
            'id' => $r->id,
            'type' => 'return',
            'status' => strtolower($r->Workflow_Status ?? 'pending'),
            'sender_condition' => $r->Sender_Condition,
            'admin_condition' => $r->Admin_Condition,
            'missing_items' => $r->Missing_Items ?? [],
            'notes' => $r->Notes,
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
            'created_at' => $r->created_at,
        ];
    }

    private function statusId(array $names): ?int
    {
        foreach ($names as $name) {
            $status = Status::whereRaw('LOWER(Status_Name) = ?', [strtolower($name)])->first();
            if ($status) {
                return (int) $status->id;
            }
        }

        return null;
    }
}
