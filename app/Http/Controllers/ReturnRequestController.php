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

    public function index(): JsonResponse
    {
        return response()->json($this->service->listAll());
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
            'asset_id' => 'required|integer|exists:assets,id',
            'sender_condition' => 'nullable|string|max:255',
            'missing_items' => 'nullable|array',
            'missing_items.*' => 'string|max:255',
            'issue_notes' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $row = $this->service->createRequest($request->user(), $data);

        return response()->json([
            'message' => 'Return request submitted.',
            'return_request' => $this->service->mapReturnRequest($row),
        ], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['status' => 'required|string|max:60']);

        $row = $this->service->updateStatus($id, $data['status']);

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
