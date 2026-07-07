<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $query = Category::query();
        
        $user = Auth::user();
        // If HOD, only show categories they created
        if ($user && $user->role === 'hod') {
            $query->where('created_by', $user->id);
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'nullable|array',
            'fields.*.name' => 'nullable|string|max:255',
            'fields.*.label' => 'required_with:fields|string|max:255',
            'fields.*.type' => 'required_with:fields|string|max:50',
            'fields.*.required' => 'nullable|boolean',
            'fields.*.options' => 'nullable',
            'slug' => 'nullable|string|unique:categories,slug', // Added validation for slug
        ]);

        $data['created_by'] = Auth::id();

        $category = Category::create($data);
        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'nullable|array',
            'fields.*.name' => 'nullable|string|max:255',
            'fields.*.label' => 'required_with:fields|string|max:255',
            'fields.*.type' => 'required_with:fields|string|max:50',
            'fields.*.required' => 'nullable|boolean',
            'fields.*.options' => 'nullable',
            'slug' => 'nullable|string|unique:categories,slug,' . $category->id, // Allow same slug on update
        ]);

        $category->update($data);
        return response()->json($category);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted']);
    }
}
