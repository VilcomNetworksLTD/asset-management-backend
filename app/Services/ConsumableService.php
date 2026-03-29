<?php

namespace App\Services;

use App\Models\AssetConsumable;
use App\Models\Consumable;

class ConsumableService
{
    public function getAll()
    {
        return Consumable::with('colorStocks')->latest()->get();
    }

    public function getUsageHistory()
    {
        // This 'with' part is the most important line in the whole project.
        // It joins the Asset table and Consumable table to the ID numbers.
        return AssetConsumable::with(['asset', 'consumable.colorStocks', 'user'])
            ->latest('installed_at')
            ->get();
    }

    public function getLowStock()
    {
        return Consumable::with(['colorStocks' => function($query) {
                $query->whereColumn('in_stock', '<=', 'min_amt')
                      ->orWhere('in_stock', 0);
            }])
            ->whereHas('colorStocks', function($query) {
                $query->whereColumn('in_stock', '<=', 'min_amt')
                      ->orWhere('in_stock', 0);
            })
            ->latest()
            ->get();
    }

    public function getUsageReport()
    {
        return AssetConsumable::with(['asset', 'consumable'])
            ->select('asset_id', 'consumable_id', \DB::raw('count(*) as count'))
            ->groupBy('asset_id', 'consumable_id')
            ->get()
            ->map(function ($item) {
                return [
                    'asset_name' => $item->asset->Asset_Name ?? 'Unknown Asset',
                    'item_name' => $item->consumable->item_name ?? 'Unknown Item',
                    'usage_count' => $item->count,
                ];
            });
    }

    public function create(array $data)
    {
        $colorStocks = $data['color_stocks'] ?? [];
        unset($data['color_stocks']);

        $consumable = Consumable::create($data);

        foreach ($colorStocks as $stock) {
            $consumable->colorStocks()->create($stock);
        }

        return $consumable->load('colorStocks');
    }

    public function update(Consumable $consumable, array $data)
    {
        $colorStocks = $data['color_stocks'] ?? [];
        unset($data['color_stocks']);

        $consumable->update($data);

        if (!empty($colorStocks)) {
            // Simple sync-like logic: identify by color
            foreach ($colorStocks as $stock) {
                $consumable->colorStocks()->updateOrCreate(
                    ['color' => $stock['color']],
                    ['in_stock' => $stock['in_stock'], 'min_amt' => $stock['min_amt']]
                );
            }
        }

        return $consumable->load('colorStocks');
    }

    public function delete(Consumable $consumable)
    {
        return $consumable->delete();
    }
}