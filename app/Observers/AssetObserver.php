<?php

namespace App\Observers;

use App\Models\Asset;
use App\Models\Status;

class AssetObserver
{
    /**
     * Handle the Asset "creating" event.
     */
    public function creating(Asset $asset): void
    {
        if (empty($asset->Status_ID)) {
            $asset->Status_ID = Status::firstOf(['Ready to Deploy', 'Available', 'Ready']) ?? 1;
        }
    }

    /**
     * Handle the Asset "updating" event.
     */
    public function updating(Asset $asset): void
    {
        if ($asset->isDirty('Employee_ID')) {
            $oldEmployeeId = $asset->getOriginal('Employee_ID');
            $newEmployeeId = $asset->Employee_ID;

            // If it was just assigned to someone
            if (is_null($oldEmployeeId) && !is_null($newEmployeeId)) {
                $statusId = Status::firstOf(['Deployed', 'Assigned', 'In Use']);
                if ($statusId) {
                    $asset->Status_ID = $statusId;
                }
            }
            // If it was just unassigned
            elseif (!is_null($oldEmployeeId) && is_null($newEmployeeId)) {
                // Only reset to Ready if it's not currently Out for Repair
                $outForRepairId = Status::firstOf(['Out for Repair', 'Maintenance']);
                if ($asset->Status_ID != $outForRepairId) {
                    $statusId = Status::firstOf(['Ready to Deploy', 'Available', 'Ready']);
                    if ($statusId) {
                        $asset->Status_ID = $statusId;
                    }
                }
            }
        }
    }
}
