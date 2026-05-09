<?php

namespace App\Services;

use App\Models\Accessory;

class AccessoryService
{
    public function getAllAccessories($type = null)
    {
        $query = Accessory::query();
        if ($type) {
            $query->where('type', $type);
        }
        return $query->get();
    }
}