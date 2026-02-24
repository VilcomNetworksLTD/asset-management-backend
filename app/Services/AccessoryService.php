<?php

namespace App\Services;

use App\Models\Accessory;

class AccessoryService
{
    public function getAllAccessories()
    {
        return Accessory::all();
    }
}