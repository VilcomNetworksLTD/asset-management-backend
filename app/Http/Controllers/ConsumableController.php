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

    public function list(Request $request): JsonResponse
    {
        $query = Consumable::query();
        if ($search = $request->string('search')->toString()) {
            $query->where('item_name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
        }
        $perPage = max(1, min(100, $request->integer('per_page', 10)));
        return response()->json($query->latest()->paginate($perPage));
    }

    // ... (Keep other existing methods like store/update/destroy)
}