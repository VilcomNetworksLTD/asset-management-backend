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
        $this->safetikaService->syncDepartments();
        return response()->json(Department::orderBy('name')->get());
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
