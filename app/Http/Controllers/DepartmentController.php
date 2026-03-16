<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(): JsonResponse
    {
        // Returns list for dropdowns, sorted alphabetically
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
