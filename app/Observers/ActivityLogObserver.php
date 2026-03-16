<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ActivityLogObserver
{
    /**
     * Handle the ActivityLog "created" event.
     * Send a short email to all admins summarizing the action.
     */
    public function created(ActivityLog $log): void
    {
        // if there are no admins with an email configured, bail out early
        $admins = User::where('role', 'admin')->whereNotNull('email')->get();
        if ($admins->isEmpty()) {
            return;
        }

        $subject = "Activity: {$log->action}";
        $details = "User: {$log->user_name}\n" .
                   "Action: {$log->action}\n" .
                   (
                       $log->target_type ? "Target: {$log->target_type}" : ''
                   ) .
                   ($log->target_name ? " {$log->target_name}\n" : "\n") .
                   ($log->details ? "Details: {$log->details}\n" : "");

        // remove any literal record IDs (e.g. "#123") before emailing
        $cleanDetails = preg_replace('/#\d+/', '', $details);

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new \App\Mail\ActivityAlert($subject, $cleanDetails));
        }
    }
}
