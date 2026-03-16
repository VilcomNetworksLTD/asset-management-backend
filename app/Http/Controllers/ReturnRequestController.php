<?php

namespace App\Http\Controllers;

use App\Services\ReturnRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnRequestController extends Controller
{
    public function __construct(private ReturnRequestService $service)
    {
    }

    /**
     * Return a paginated list of requests for the admin UI.  By default this
     * returns only "open" requests so that the table does not grow endlessly
     * and become slow; the frontend passes ?pending=1 when loading the
     * return-assets page.  Clients may request additional pages via per_page.
     */
    public function index(Request $request): JsonResponse
    {
        $query = \App\Models\ReturnRequest::with(['asset.status', 'sender', 'actionedBy']);

        if ($request->boolean('pending')) {
            // hide items that have been closed or rejected – everything else is
            // still actionable (pending_inspection, inspected, approved, etc.)
            $query->whereNotIn('Workflow_Status', ['closed', 'rejected']);
        }

        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $paginated = $query->latest()->paginate($perPage);

        // map results using the shared service helper so the shape stays
        // consistent with other endpoints
        $paginated->getCollection()->transform(fn($r) => $this->service->mapReturnRequest($r));

        return response()->json($paginated);
    }

    public function myRequests(Request $request): JsonResponse
    {
        return response()->json($this->service->listMine($request->user()->id));
    }

    public function myAssets(Request $request): JsonResponse
    {
        return response()->json($this->service->getMyAssets($request->user()->id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => 'nullable|integer|exists:assets,id',
            'items' => 'nullable|array',
            'items.*.type' => 'required_with:items|string|in:asset,component,accessory,license,consumable',
            'items.*.id' => 'required_with:items|integer',
            'sender_condition' => 'nullable|string|max:255',
            'missing_items' => 'nullable|array',
            'missing_items.*' => 'string|max:255',
            'issue_notes' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        // A request must contain either a primary asset or at least one other item.
        if (empty($data['asset_id']) && empty($data['items'])) {
            return response()->json(['message' => 'You must select an asset or at least one component/accessory to return.'], 422);
        }

        $row = $this->service->createRequest($request->user(), $data);

        return response()->json([
            'message' => 'Return request submitted.',
            'return_request' => $this->service->mapReturnRequest($row),
        ], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|max:60',
            'reason' => 'nullable|string|max:2000',
        ]);

        $row = $this->service->updateStatus($id, $data['status'], $data['reason'] ?? null);

        return response()->json([
            'message' => 'Return request status updated.',
            'return_request' => $this->service->mapReturnRequest($row),
        ]);
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

        $row = $this->service->completeInspection($id, $request->user()->id, $data);

        return response()->json([
            'message' => 'Return inspection completed.',
            'return_request' => $this->service->mapReturnRequest($row),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->service->destroy($id, $request->user());

        return response()->json(['message' => 'Return request deleted successfully.']);
    }
}
