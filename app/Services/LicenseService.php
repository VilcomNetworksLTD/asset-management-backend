<?php

namespace App\Services;

use App\Models\License;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class LicenseService
{
    /**
     * Fetch all licenses from the database.
     */
    public function getAllLicenses()
    {
        return License::all();
    }

    /**
     * Find a single license by ID.
     */
    public function getLicenseById(int $id): License
    {
        return License::findOrFail($id);
    }

    /**
     * Create a new license record and log the activity.
     */
    public function store(array $data): License
    {
        $license = License::create($data);

        ActivityLog::create([
            'Employee_ID' => Auth::id(),
            'user_name'   => Auth::user()->name ?? 'System',
            'action'      => 'Created',
            'target_type' => 'License',
            'target_name' => $license->name,
            'details'     => "Manufacturer: {$license->manufacturer}; Seats: {$license->total_seats}",
        ]);

        return $license;
    }

    /**
     * Update an existing license and log the activity.
     */
    public function updateLicense(int $id, array $data): License
    {
        $license = License::findOrFail($id);
        $license->update($data);

        ActivityLog::create([
            'Employee_ID' => Auth::id(),
            'user_name'   => Auth::user()->name ?? 'System',
            'action'      => 'Updated',
            'target_type' => 'License',
            'target_name' => $license->name,
            'details'     => 'License details updated.',
        ]);

        return $license->fresh();
    }

    /**
     * Delete a license and log the activity.
     */
    public function deleteLicense(int $id): bool
    {
        $license = License::findOrFail($id);
        
        ActivityLog::create([
            'Employee_ID' => Auth::id(),
            'user_name'   => Auth::user()->name ?? 'System',
            'action'      => 'Deleted',
            'target_type' => 'License',
            'target_name' => $license->name,
            'details'     => 'License removed from system.',
        ]);

        return $license->delete();
    }
}