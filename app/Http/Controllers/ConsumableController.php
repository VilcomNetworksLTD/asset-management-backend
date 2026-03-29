<?php

namespace App\Http\Controllers;

use App\Models\Consumable;
use App\Models\AssetConsumable;
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

    /**
     * This method provides the data for the Lifecycle Table.
     * Eager loading 'asset' and 'consumable' is what fixes the "Unknown" text.
     */
    public function usageHistory(): JsonResponse
    {
        // Delegate logic to the service layer for consistency.
        return response()->json($this->consumableService->getUsageHistory());
    }

    public function lowStock(): JsonResponse
    {
        return response()->json($this->consumableService->getLowStock());
    }

    public function usageReport(): JsonResponse
    {
        return response()->json($this->consumableService->getUsageReport());
    }

    public function list(Request $request): JsonResponse
    {
        $query = Consumable::with('colorStocks');
        if ($search = $request->string('search')->toString()) {
            $query->where(function($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhereHas('colorStocks', function($sq) use ($search) {
                      $sq->where('color', 'like', "%{$search}%");
                  });
            });
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', 'like', "%{$category}%");
        }
        $perPage = max(1, min(100, $request->integer('per_page', 10)));
        return response()->json($query->latest()->paginate($perPage));
    }
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'color_stocks' => 'required|array|min:1',
            'color_stocks.*.color' => 'required|string|max:255',
            'color_stocks.*.in_stock' => 'required|integer|min:0',
            'color_stocks.*.min_amt' => 'required|integer|min:0',
        ]);

        $consumable = $this->consumableService->create($validated);
        return response()->json($consumable, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $consumable = Consumable::findOrFail($id);
        
        $validated = $request->validate([
            'item_name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'color_stocks' => 'nullable|array',
            'color_stocks.*.color' => 'required_with:color_stocks|string|max:255',
            'color_stocks.*.in_stock' => 'required_with:color_stocks|integer|min:0',
            'color_stocks.*.min_amt' => 'required_with:color_stocks|integer|min:0',
        ]);

        $this->consumableService->update($consumable, $validated);
        return response()->json($consumable);
    }

    public function destroy(int $id): JsonResponse
    {
        $consumable = Consumable::findOrFail($id);
        $this->consumableService->delete($consumable);
        return response()->json(['message' => 'Consumable deleted successfully']);
    }
}