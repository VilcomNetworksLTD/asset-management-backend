<?php

namespace App\Services;

use App\Models\Consumable; // Ensure this model exists

class ConsumableService
{
    public function getAll()
    {
        return Consumable::all();
    }
}