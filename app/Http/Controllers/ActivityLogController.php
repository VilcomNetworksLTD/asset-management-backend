<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    protected $logService;

    public function __construct(ActivityLogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Display a listing of activities.
     */
    public function index(): JsonResponse
    {
        $logs = $this->logService->getRecentLogs();
        return response()->json($logs);
    }
}