<?php

namespace App\Services;

use App\Models\Maintenance;
use Illuminate\Support\Collection;

class MaintenanceService
{
    /**
     * Get all maintenance records with related names.
     */
    public function getAllMaintenances(): Collection
    {
        // We eager load 'asset' and 'status' to avoid extra database queries
        return Maintenance::with(['asset', 'status'])->latest()->get();
    }

    /**
     * Get only active maintenances (where completion date is missing).
     */
    public function getActiveMaintenances(): Collection
    {
        return Maintenance::with(['asset', 'status'])
            ->whereNull('Completion_Date')
            ->get();
    }
}