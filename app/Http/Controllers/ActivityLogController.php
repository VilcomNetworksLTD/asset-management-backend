<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
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
        $query = \App\Models\ActivityLog::with('user');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('target_name', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('details', 'like', "%{$search}%");
            });
        }

        $perPage = $request->integer('per_page', 50);
        $logs = $query->latest()->paginate($perPage);
        
        return response()->json($logs);
    }
}