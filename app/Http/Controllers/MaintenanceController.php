<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Asset;
use App\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        }

        $perPage = max(1, min(100, $request->integer('per_page', 10)));

        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Asset_ID' => 'required|integer|exists:assets,id',
            'Ticket_ID' => 'nullable|integer|exists:tickets,id',
            'Request_Date' => 'required|date',
            'Completion_Date' => 'nullable|date|after_or_equal:Request_Date',
            'Maintenance_Type' => 'required|string|max:255',
            'Description' => 'nullable|string',
            'Cost' => 'nullable|numeric|min:0',
            'Status_ID' => 'required|integer|exists:statuses,id',
            'asset_status_id' => 'nullable|integer|exists:statuses,id',
            'Maintenance_Date' => 'nullable|date',
        ]);

        $maintenance = DB::transaction(function () use ($data) {
            $maintenance = Maintenance::create(collect($data)->except(['asset_status_id'])->toArray());

            if (!empty($data['asset_status_id'])) {
                Asset::where('id', $maintenance->Asset_ID)->update([
                    'Status_ID' => (int) $data['asset_status_id'],
                ]);
            }

            return $maintenance;
        });

        $maintenance->load(['asset.status', 'status']);

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
            'asset_status_id' => 'nullable|integer|exists:statuses,id',
            'Maintenance_Date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($maintenance, $data) {
            $maintenance->update(collect($data)->except(['asset_status_id'])->toArray());

            if (array_key_exists('asset_status_id', $data) && !empty($data['asset_status_id'])) {
                Asset::where('id', $maintenance->Asset_ID)->update([
                    'Status_ID' => (int) $data['asset_status_id'],
                ]);
            }
        });

        return response()->json($maintenance->fresh()->load(['asset.status', 'status']));
    }

    public function destroy(int $id): JsonResponse
    {
        $maintenance = Maintenance::findOrFail($id);
        $maintenance->delete();

        return response()->json(['message' => 'Maintenance record deleted successfully']);
    }
}