<?php

namespace App\Http\Controllers;

use App\Services\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    protected $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    /**
     * List feedback (paginated version for your frontend table).
     */
    public function index(Request $request): JsonResponse
    {
        // Using basic query for the list view
        $perPage = $request->integer('per_page', 10);
        $feedback = \App\Models\Feedback::with(['asset', 'employee'])
            ->latest()
            ->paginate($perPage);

        return response()->json($feedback);
    }

    /**
     * Store new feedback from a user.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Asset_ID'    => 'required|exists:assets,id',
            'Employee_ID' => 'required|exists:users,id',
            'Comments'    => 'required|string',
        ]);

        $feedback = $this->feedbackService->store($data);

        return response()->json($feedback, 201);
    }

    /**
     * Update feedback comments.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'Comments' => 'required|string',
        ]);

        $feedback = $this->feedbackService->updateFeedback($id, $data);

        return response()->json($feedback);
    }

    /**
     * Remove feedback.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->feedbackService->deleteFeedback($id);

        return response()->json(['message' => 'Feedback deleted successfully']);
    }
}