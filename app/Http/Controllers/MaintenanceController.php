<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Status;
use App\Models\Asset;
use App\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\MaintenanceAlert;

class MaintenanceController extends Controller
{
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
            // Find a generic "Pending" or "Open" status to assign.
            $data['Status_ID'] = Status::firstOf(['Pending', 'New', 'Open']) ?? 1;
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
        $assetOwner = $maintenance->asset?->employee;
        $subject = "Maintenance scheduled for asset #{$maintenance->Asset_ID}";
        $details = "A maintenance task has been scheduled:\n" .
            "Type: {$maintenance->Maintenance_Type}\n" .
            "Asset ID: {$maintenance->Asset_ID}\n" .
            "Description: {$maintenance->Description}\n" .
            "Request Date: {$maintenance->Request_Date}";
        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new MaintenanceAlert($maintenance, $admin));
        }
        if ($assetOwner && $assetOwner->email) {
            Mail::to($assetOwner->email)->send(new MaintenanceAlert($maintenance, $assetOwner));
        }

        return response()->json($maintenance, 201);
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

        return response()->json($maintenance->fresh()->load(['asset.status', 'status']));
    }

    public function archive(int $id): JsonResponse
    {
        $maintenance = Maintenance::findOrFail($id);
        
        $maintenance->transitionTo(Maintenance::WORKFLOW_ARCHIVED, auth()->user());

        return response()->json([
            'message' => 'Asset archived successfully from maintenance',
            'maintenance' => $maintenance->load(['asset.status', 'status'])
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $maintenance = Maintenance::findOrFail($id);
        $maintenance->delete();

        return response()->json(['message' => 'Maintenance record deleted successfully']);
    }
}