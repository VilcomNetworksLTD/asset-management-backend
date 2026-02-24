<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Record a new activity in the database.
     */
    public function log($action, $targetType, $targetName, $details = null)
    {
        return ActivityLog::create([
            'user_name'   => Auth::user() ? Auth::user()->name : 'System',
            'action'      => $action,      // e.g., 'Created', 'Updated'
            'target_type' => $targetType,  // e.g., 'Asset', 'License'
            'target_name' => $targetName,  // e.g., 'MacBook Pro #001'
            'details'     => $details
        ]);
    }

    /**
     * Fetch the most recent logs for the UI.
     */
    public function getRecentLogs($limit = 50)
    {
        return ActivityLog::latest()->take($limit)->get();
    }
}