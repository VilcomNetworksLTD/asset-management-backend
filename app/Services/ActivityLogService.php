<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    // Added assetId as optional param to fix log helper mapping
    public function log($action, $targetType, $targetName, $details = null, $assetId = null)
    {
        $user = Auth::user();

        return ActivityLog::create([
            'asset_id' => $assetId,
            'Employee_ID' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'System',
            'action' => $action,
            'target_type' => $targetType,
            'target_name' => $targetName,
            'details' => $details
        ]);
    }

    public function getRecentLogs($limit = 50, $search = null)
    {
        $query = ActivityLog::with('user')->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('target_type', 'like', "%{$search}%")
                    ->orWhere('target_name', 'like', "%{$search}%")
                    ->orWhere('details', 'like', "%{$search}%");
            });
        }

        // Return paginated or limited results
        return $query->paginate($limit);
    }
}