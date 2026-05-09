<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\SafetikaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    protected $safetikaService;

    public function __construct(SafetikaService $safetikaService)
    {
        $this->safetikaService = $safetikaService;
    }

    public function index(): JsonResponse
    {
        // syncDepartments now handles fetching a system token if none provided
        $this->safetikaService->syncDepartments();
        return response()->json(Department::orderBy('name')->get());
    }

    public function debugSync(): JsonResponse
    {
        $hubConfig = config('services.safetika_hub');
        
        $result = [
            'hub_url' => $hubConfig['url'] ?? null,
            'configured' => !empty($hubConfig['url']),
            'departments_count' => Department::count(),
            'recent_logs' => \DB::table('activity_logs')
                ->where('description', 'like', '%department%')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];
        
        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|unique:departments,name|max:255',
            'description' => 'nullable|string'
        ]);
        
        $dept = Department::create($data);
        return response()->json($dept, 201);
    }
}
