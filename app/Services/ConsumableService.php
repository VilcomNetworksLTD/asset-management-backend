<?php

namespace App\Services;

use App\Models\AssetConsumable;
use App\Models\Consumable;

class ConsumableService
{
    public function getAll()
    {
        return Consumable::latest()->get();
    }

    public function getUsageHistory()
    {
        // This 'with' part is the most important line in the whole project.
        // It joins the Asset table and Consumable table to the ID numbers.
        return AssetConsumable::with(['asset', 'consumable'])
            ->latest('installed_at')
            ->get();
    }
}