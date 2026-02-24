<?php

namespace App\Http\Controllers;

use App\Models\Consumable;
use App\Services\ConsumableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsumableController extends Controller
{
    protected $consumableService;

    public function __construct(ConsumableService $consumableService) 
    {
        $this->consumableService = $consumableService;
    }

    public function index(): JsonResponse
    {
        return response()->json($this->consumableService->getAll());
    }

    public function list(Request $request): JsonResponse
    {
        $query = Consumable::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        $perPage = max(1, min(100, $request->integer('per_page', 10)));

        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'in_stock' => 'required|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'min_amt' => 'nullable|integer|min:0',
        ]);

        $consumable = Consumable::create($data);

        return response()->json($consumable, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $consumable = Consumable::findOrFail($id);

        $data = $request->validate([
            'item_name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:255',
            'in_stock' => 'sometimes|required|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'min_amt' => 'nullable|integer|min:0',
        ]);

        $consumable->update($data);

        return response()->json($consumable->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $consumable = Consumable::findOrFail($id);
        $consumable->delete();

        return response()->json(['message' => 'Consumable deleted successfully']);
    }
}