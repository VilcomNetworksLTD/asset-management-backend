<?php

namespace App\Services;

use App\Models\Location;

class LocationService
{
    /**
     * Get all locations sorted alphabetically.
     */
    public function getAllLocations()
    {
        return Location::orderBy('name', 'asc')->get();
    }

    /**
     * Create a new location.
     */
    public function createLocation(array $data)
    {
        return Location::create($data);
    }
}