<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class FeedbackService
{
    /**
     * Fetch feedback with related asset and employee info.
     */
    public function getAllFeedback()
    {
        // Based on your image, feedback links to Assets and Employees
        return Feedback::with(['asset', 'employee'])->latest()->get();
    }

    /**
     * Store feedback and log activity.
     */
    public function store(array $data): Feedback
    {
        $feedback = Feedback::create($data);

        ActivityLog::create([
            'Employee_ID' => Auth::id(),
            'user_name'   => Auth::user()->name ?? 'System',
            'action'      => 'Created',
            'target_type' => 'Feedback',
            'target_name' => "Feedback for Asset #{$feedback->Asset_ID}",
            'details'     => "Comment: " . substr($feedback->Comments, 0, 50) . "...",
        ]);

        return $feedback->load(['asset', 'employee']);
    }

    /**
     * Update existing feedback comments.
     */
    public function updateFeedback(int $id, array $data): Feedback
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->update($data);

        ActivityLog::create([
            'Employee_ID' => Auth::id(),
            'user_name'   => Auth::user()->name ?? 'System',
            'action'      => 'Updated',
            'target_type' => 'Feedback',
            'target_name' => "Feedback #{$id}",
            'details'     => 'Feedback comments were modified.',
        ]);

        return $feedback->fresh(['asset', 'employee']);
    }

    /**
     * Delete feedback.
     */
    public function deleteFeedback(int $id): bool
    {
        $feedback = Feedback::findOrFail($id);
        
        ActivityLog::create([
            'Employee_ID' => Auth::id(),
            'user_name'   => Auth::user()->name ?? 'System',
            'action'      => 'Deleted',
            'target_type' => 'Feedback',
            'target_name' => "Feedback #{$id}",
            'details'     => 'Feedback removed from database.',
        ]);

        return $feedback->delete();
    }
}