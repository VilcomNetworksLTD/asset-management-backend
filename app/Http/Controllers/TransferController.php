<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\Status;
use App\Models\Transfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = Transfer::with(['asset.status', 'sender', 'receiver', 'actionedBy'])
            ->latest()
            ->get()
            ->map(fn ($t) => $this->mapTransfer($t));

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->storeReturnRequest($request);
    }

    public function getUserTransfers(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $rows = Transfer::with(['asset.status', 'sender', 'receiver', 'actionedBy'])
            ->where(function ($q) use ($userId) {
                $q->where('Sender_ID', $userId)
                    ->orWhere('Receiver_ID', $userId)
                    ->orWhere('Employee_ID', $userId);
            })
            ->latest()
            ->get()
            ->map(fn ($t) => $this->mapTransfer($t));

        return response()->json($rows);
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

        if (!$isAdmin && !$isOwnerPending) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transfer->delete();

        return response()->json(['message' => 'Transfer deleted successfully.']);
    }

    public function getMyAssets(Request $request): JsonResponse
    {
        $user = $request->user();

        $assets = Asset::where('Employee_ID', $user->id)
            ->get(['id', 'Asset_Name', 'Serial_No'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'model' => $a->Asset_Name,
                'serial' => $a->Serial_No,
            ]);

        return response()->json($assets);
    }

    public function storeReturnRequest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => 'required|integer|exists:assets,id',
            'type' => 'required|in:return,transfer',
            'receiver_id' => 'nullable|integer|exists:users,id',
            'sender_condition' => 'nullable|string|max:255',
            'missing_items' => 'nullable|array',
            'missing_items.*' => 'string|max:255',
            'issue_notes' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        $asset = Asset::findOrFail($data['asset_id']);

        if ((int) $asset->Employee_ID !== (int) $user->id) {
            return response()->json(['message' => 'You can only request transfer for assets assigned to you.'], 403);
        }

        if ($data['type'] === 'transfer' && empty($data['receiver_id'])) {
            return response()->json(['message' => 'Receiver is required for transfer type.'], 422);
        }

        $transfer = Transfer::create([
            'Asset_ID' => $asset->id,
            'Employee_ID' => $user->id,
            'Sender_ID' => $user->id,
            'Receiver_ID' => $data['receiver_id'] ?? null,
            'Status_ID' => $this->statusId(['Pending', 'Requested']) ?? $asset->Status_ID,
            'Transfer_Date' => now(),
            'Type' => $data['type'],
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
            'action' => 'Transfer Requested',
            'target_type' => 'Asset',
            'target_name' => $asset->Asset_Name,
            'details' => "Transfer #{$transfer->id} submitted for admin recall/inspection",
        ]);

        return response()->json(['message' => 'Transfer request submitted.', 'transfer' => $this->mapTransfer($transfer->fresh(['asset.status', 'sender', 'receiver', 'actionedBy']))], 201);
    }

    public function indexPending(): JsonResponse
    {
        $rows = Transfer::with(['asset.status', 'sender', 'receiver'])
            ->where('Workflow_Status', 'pending_inspection')
            ->latest()
            ->get()
            ->map(fn ($t) => $this->mapTransfer($t));

        return response()->json($rows);
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

        $transfer = Transfer::with('asset')->findOrFail($id);
        $asset = $transfer->asset;

        DB::transaction(function () use ($transfer, $asset, $data) {
            $statusCandidates = match ($data['disposition']) {
                'ready_to_deploy' => ['Ready to Deploy', 'Available'],
                'non_deployable' => ['Non-Deployable', 'Archived/Lost', 'Retired'],
                'maintenance' => ['Out for Repair', 'Maintenance', 'Pending'],
            };

            // asset returns to admin custody after recall inspection
            $asset->update([
                'Employee_ID' => Auth::id(),
                'Status_ID' => $this->statusId($statusCandidates) ?? $asset->Status_ID,
            ]);

            $next = ($transfer->Type === 'transfer' && $transfer->Receiver_ID)
                ? 'pending_verification'
                : 'completed';

            $transfer->update([
                'Workflow_Status' => $next,
                'Admin_Condition' => $data['condition'],
                'Missing_Items' => $data['missing_items'] ?? [],
                'Notes' => trim(collect([
                    $transfer->Notes,
                    !empty($data['admin_notes']) ? ('Admin notes: ' . $data['admin_notes']) : null,
                    'Disposition: ' . str_replace('_', ' ', $data['disposition']),
                ])->filter()->implode(' | ')),
                'Actioned_By' => Auth::id(),
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

        return response()->json(['message' => 'Inspection completed.', 'transfer' => $this->mapTransfer($transfer->fresh(['asset.status', 'sender', 'receiver', 'actionedBy']))]);
    }

    public function assignToUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => 'required|integer|exists:assets,id',
            'receiver_id' => 'required|integer|exists:users,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        $asset = Asset::findOrFail($data['asset_id']);

        $transfer = Transfer::create([
            'Asset_ID' => $asset->id,
            'Employee_ID' => $data['receiver_id'],
            'Sender_ID' => Auth::id(),
            'Receiver_ID' => $data['receiver_id'],
            'Status_ID' => $this->statusId(['Pending', 'Requested']) ?? $asset->Status_ID,
            'Transfer_Date' => now(),
            'Type' => 'assignment',
            'Workflow_Status' => 'pending_verification',
            'Admin_Condition' => 'Good',
            'Included_Items' => ['Charger'],
            'Notes' => $data['notes'] ?? 'Assigned by admin',
            'Actioned_By' => Auth::id(),
            'Actioned_At' => now(),
        ]);

        // keep admin custody until receiver verifies inbound
        $asset->update([
            'Employee_ID' => Auth::id(),
            'Status_ID' => $this->statusId(['Pending', 'Ready to Deploy', 'Available']) ?? $asset->Status_ID,
        ]);

        return response()->json(['message' => 'Assignment created, awaiting staff verification.', 'transfer' => $this->mapTransfer($transfer->fresh(['asset.status', 'sender', 'receiver', 'actionedBy']))], 201);
    }

    public function getPendingAssignments(Request $request): JsonResponse
    {
        $user = $request->user();

        $rows = Transfer::with(['asset', 'actionedBy'])
            ->where('Receiver_ID', $user->id)
            ->where('Workflow_Status', 'pending_verification')
            ->latest()
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'admin_condition' => $t->Admin_Condition,
                'included_items' => $t->Included_Items ?? ['Charger'],
                'admin' => $t->actionedBy ? ['id' => $t->actionedBy->id, 'name' => $t->actionedBy->name] : ['name' => 'System Admin'],
                'asset' => $t->asset ? [
                    'id' => $t->asset->id,
                    'model' => $t->asset->Asset_Name,
                    'serial' => $t->asset->Serial_No,
                    'asset_tag' => 'AST-' . str_pad((string) $t->asset->id, 4, '0', STR_PAD_LEFT),
                ] : null,
            ]);

        return response()->json($rows);
    }

    public function verifyInbound(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:accepted,disputed',
            'notes' => 'nullable|string|max:2000',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $transfer = Transfer::with('asset')
                ->where('Receiver_ID', Auth::id())
                ->findOrFail($id);

            $asset = $transfer->asset;

            if ($request->status === 'accepted') {
                $transfer->update([
                    'Workflow_Status' => 'deployed',
                    'Actioned_At' => now(),
                ]);

                $asset->update([
                    'Employee_ID' => Auth::id(),
                    'Status_ID' => $this->statusId(['Deployed', 'Assigned', 'In Use']) ?? $asset->Status_ID,
                ]);

                return response()->json(['message' => 'Asset verified and assigned to you.']);
            }

            $transfer->update([
                'Workflow_Status' => 'disputed',
                'Notes' => $request->notes ?? 'User reported discrepancy during inbound verification.',
            ]);

            $asset->update([
                'Employee_ID' => $transfer->Sender_ID ?: Auth::id(),
                'Status_ID' => $this->statusId(['Ready to Deploy', 'Available']) ?? $asset->Status_ID,
            ]);

            return response()->json(['message' => 'Discrepancy reported to admin.'], 422);
        });
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

    private function mapTransfer(Transfer $t): array
    {
        return [
            'id' => $t->id,
            'type' => strtolower($t->Type ?? 'transfer'),
            'status' => strtolower($t->Workflow_Status ?? 'pending'),
            'sender_condition' => $t->Sender_Condition,
            'admin_condition' => $t->Admin_Condition,
            'included_items' => $t->Included_Items ?? [],
            'missing_items' => $t->Missing_Items ?? [],
            'notes' => $t->Notes,
            'sender' => $t->sender ? ['id' => $t->sender->id, 'name' => $t->sender->name] : null,
            'receiver' => $t->receiver ? ['id' => $t->receiver->id, 'name' => $t->receiver->name] : null,
            'admin' => $t->actionedBy ? ['id' => $t->actionedBy->id, 'name' => $t->actionedBy->name] : null,
            'asset' => $t->asset ? [
                'id' => $t->asset->id,
                'model' => $t->asset->Asset_Name,
                'serial' => $t->asset->Serial_No,
                'asset_tag' => 'AST-' . str_pad((string) $t->asset->id, 4, '0', STR_PAD_LEFT),
                'status_name' => optional($t->asset->status)->Status_Name,
            ] : null,
            'created_at' => $t->created_at,
        ];
    }
}
