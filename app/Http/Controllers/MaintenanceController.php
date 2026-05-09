<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Status;
use App\Models\Asset;
use App\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\SendsDetailedEmails;

class MaintenanceController extends Controller
{
    use SendsDetailedEmails;

    protected $maintenanceService;

    /**
     * Inject the MaintenanceService.
     */
    public function __construct(MaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    public function index(): JsonResponse
    {
        $logs = $this->maintenanceService->getAllMaintenances();
        return response()->json($logs);
    }

    public function list(Request $request): JsonResponse
    {
        $query = Maintenance::with(['asset.status', 'status']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('Maintenance_Type', 'like', "%{$search}%")
                    ->orWhere('Description', 'like', "%{$search}%");
            });
        }

        if ($statusId = $request->integer('status_id')) {
            $query->where('Status_ID', $statusId);
        } elseif ($statusName = $request->string('status_name')->toString()) {
            $query->whereHas('status', function ($q) use ($statusName) {
                $q->where('Status_Name', 'like', "%{$statusName}%");
            });
        }

        $perPage = max(1, min(100, $request->integer('per_page', 10)));

        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Asset_ID' => 'required|integer|exists:assets,id',
            'Ticket_ID' => 'nullable|integer|exists:tickets,id',
            'Request_Date' => 'nullable|date',
            'Completion_Date' => 'nullable|date',
            'Maintenance_Type' => 'required|string|max:255',
            'Description' => 'nullable|string',
            'Cost' => 'nullable|numeric|min:0',
            'Status_ID' => 'nullable|integer|exists:statuses,id',
            'asset_status_id' => 'nullable|integer|exists:statuses,id',
            'Maintenance_Date' => 'nullable|date',
            'is_undeployable' => 'nullable|boolean',
        ]);

        // Provide sensible defaults for fields that might be omitted by a simple form.
        $data['Request_Date'] = $data['Request_Date'] ?? now();
        if (empty($data['Status_ID'])) {
            $data['Status_ID'] = Status::firstOf(['Out for Repair', 'Maintenance', 'Pending']) ?? 1;
        }

        $maintenance = DB::transaction(function () use ($data) {
            $maintenance = Maintenance::create(collect($data)->except(['asset_status_id', 'is_undeployable'])->toArray());

            if (!empty($data['is_undeployable']) && $data['is_undeployable']) {
                $maintenance->transitionTo(Maintenance::WORKFLOW_ARCHIVED);
            } elseif (!empty($data['asset_status_id'])) {
                Asset::where('id', $maintenance->Asset_ID)->update([
                    'Status_ID' => (int) $data['asset_status_id'],
                ]);
            }

            return $maintenance;
        });

        $maintenance->load(['asset.status', 'status']);

        // notify relevant parties
        $admins = \App\Models\User::where('role', 'admin')->get()->filter(fn($u)=> $u->email);
        $assetOwner = $maintenance->asset?->user; 

        $this->sendDetailedEmail(
            $admins,
            "Maintenance Scheduled",
            "Equipment Service Logged",
            "A new maintenance task has been scheduled for an asset.",
            [
                "Asset" => $maintenance->asset?->Asset_Name ?? 'N/A',
                "Service Type" => $maintenance->Maintenance_Type,
                "Scheduled Date" => $maintenance->Maintenance_Date ?? $maintenance->Request_Date,
                "Description" => $maintenance->Description ?? 'Routine Service',
            ],
            "Manage Maintenance",
            config('app.url') . "/dashboard/operations/maintenance"
        );

        if ($assetOwner) {
            $this->sendDetailedEmail(
                $assetOwner,
                "Asset Maintenance Scheduled",
                "Operational Support",
                "Your assigned asset has been scheduled for maintenance.",
                [
                    "Asset" => $maintenance->asset?->Asset_Name,
                    "Maintenance Type" => $maintenance->Maintenance_Type,
                    "Expected Date" => $maintenance->Maintenance_Date ?? $maintenance->Request_Date,
                ],
                "View My Assets",
                config('app.url') . "/dashboard/user/workspace"
            );
        }

        return response()->json(['success' => true, 'message' => 'Maintenance scheduled successfully.'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $maintenance = Maintenance::findOrFail($id);

        $data = $request->validate([
            'Asset_ID' => 'sometimes|required|integer|exists:assets,id',
            'Ticket_ID' => 'nullable|integer|exists:tickets,id',
            'Request_Date' => 'sometimes|required|date',
            'Completion_Date' => 'nullable|date',
            'Maintenance_Type' => 'sometimes|required|string|max:255',
            'Description' => 'nullable|string',
            'Cost' => 'nullable|numeric|min:0',
            'Status_ID' => 'sometimes|required|integer|exists:statuses,id',
            'Workflow_Status' => 'nullable|string|in:Scheduled,In Progress,On Hold,Completed,Cancelled,Out for Repair,Archived',
            'Maintenance_Date' => 'nullable|date',
        ]);

        $maintenance = DB::transaction(function () use ($maintenance, $data) {
            $workflowStatus = $data['Workflow_Status'] ?? null;
            
            // Logic: If Completion_Date is being set for the first time, auto-complete
            if (!empty($data['Completion_Date']) && empty($maintenance->Completion_Date)) {
                $workflowStatus = Maintenance::WORKFLOW_COMPLETED;
                $data['Status_ID'] = Status::where('Status_Name', 'Closed')->value('id') ?? Status::where('Status_Name', 'Completed')->value('id') ?? ($data['Status_ID'] ?? $maintenance->Status_ID);
            }

            // If the status comes from the Status_ID, try to map it back to workflow if possible
            if (empty($workflowStatus) && !empty($data['Status_ID'])) {
                $status = Status::find($data['Status_ID']);
                if ($status) {
                    $workflowStatus = $status->Status_Name;
                }
            }

            if ($workflowStatus) {
                $maintenance->transitionTo($workflowStatus, auth()->user(), collect($data)->except(['Workflow_Status', 'Status_ID'])->toArray());
            } else {
                $maintenance->update(collect($data)->except(['Workflow_Status'])->toArray());
            }
            
            return $maintenance;
        });

        return response()->json(['success' => true, 'message' => 'Maintenance record updated.']);
    }

    public function archive(int $id): JsonResponse
    {
        $maintenance = Maintenance::findOrFail($id);
        
        $maintenance->transitionTo(Maintenance::WORKFLOW_ARCHIVED, auth()->user());

        return response()->json([
            'success' => true, 'message' => 'Asset and record archived successfully.'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $maintenance = Maintenance::findOrFail($id);
        $maintenance->delete();

        return response()->json(['message' => 'Maintenance record deleted successfully']);
    }
}