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
        
        $limit = $request->query('limit', 50);
        

        $logs = $this->logService->getRecentLogs($limit);
        
        return response()->json($logs);
    }
}