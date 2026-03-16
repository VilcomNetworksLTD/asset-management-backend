<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    
    public function log($action, $targetType, $targetName, $details = null)
{
    $user = Auth::user();

    return ActivityLog::create([
        'asset_id',
        'Employee_ID' => $user ? $user->id : null, 
        'user_name'   => $user ? $user->name : 'System',
        'action'      => $action,
        'target_type' => $targetType,
        'target_name' => $targetName,
        'details'     => $details
    ]);
}

    /**
     * Fetch the most recent logs for the UI.
     */
public function getRecentLogs($limit = 50)
{
    // Eager load the 'user' relationship defined in your ActivityLog model
    return ActivityLog::with('user')
        ->latest()
        ->take($limit)
        ->get();
}
}