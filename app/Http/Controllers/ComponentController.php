<?php

namespace App\Http\Controllers;

use App\Models\Component;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    /**
     * Display a listing of components.
     */
    public function index(): JsonResponse
    {
        $components = Component::all();
        return response()->json($components);
    }

    public function list(Request $request): JsonResponse
    {
        $query = Component::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('serial_no', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        $perPage = max(1, min(100, $request->integer('per_page', 10)));

        return response()->json($query->latest()->paginate($perPage));
    }

    /**
     * Store a new component.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'serial_no' => 'nullable|string',
            'total_qty' => 'required|integer',
            'remaining_qty' => 'nullable|integer',
            'price' => 'nullable|numeric'
        ]);

        if (!isset($validated['remaining_qty'])) {
            $validated['remaining_qty'] = $validated['total_qty'];
        }

        $component = Component::create($validated);
        return response()->json($component, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $component = Component::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'category' => 'sometimes|required|string',
            'serial_no' => 'nullable|string',
            'total_qty' => 'sometimes|required|integer',
            'remaining_qty' => 'nullable|integer',
            'price' => 'nullable|numeric'
        ]);

        $component->update($validated);

        return response()->json($component->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $component = Component::findOrFail($id);
        $component->delete();

        return response()->json(['message' => 'Component deleted successfully']);
    }
}