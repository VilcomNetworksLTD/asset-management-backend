<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    protected $logService;

    public function __construct(ActivityLogService $logService)
    {
        $this->logService = $logService;
    }

    
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 50);
        $search = $request->query('search');

        // We use a query builder to allow for dynamic filtering
        // Include withTrashed to prevent null user relationships for deleted staff
        $query = ActivityLog::with(['user' => function($q) {
            $q->withTrashed();
        }]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('target_type', 'like', "%{$search}%")
                  ->orWhere('target_name', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Order by latest and paginate
        $logs = $query->latest()->paginate($limit);

        return response()->json($logs);
    }
}