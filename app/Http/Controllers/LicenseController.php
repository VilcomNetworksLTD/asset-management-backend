<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    /**
     * Fetch all licenses for the list view.
     */
    public function index(): JsonResponse
    {
        return response()->json(License::all());
    }

    public function list(Request $request): JsonResponse
    {
        $query = License::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('product_key', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        if ($manufacturer = $request->string('manufacturer')->toString()) {
            $query->where('manufacturer', $manufacturer);
        }

        $perPage = max(1, min(100, $request->integer('per_page', 10)));

        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'product_key' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'total_seats' => 'required|integer|min:1',
            'remaining_seats' => 'required|integer|min:0',
            'price' => 'nullable|numeric|min:0',
        ]);

        $license = License::create($data);

        return response()->json($license, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $license = License::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'product_key' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'total_seats' => 'sometimes|required|integer|min:1',
            'remaining_seats' => 'sometimes|required|integer|min:0',
            'price' => 'nullable|numeric|min:0',
        ]);

        $license->update($data);

        return response()->json($license->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $license = License::findOrFail($id);
        $license->delete();

        return response()->json(['message' => 'License deleted successfully']);
    }
}