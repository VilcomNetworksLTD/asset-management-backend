<?php

namespace App\Observers;

use App\Models\Maintenance;
use App\Models\Asset;
use App\Models\Status;

class MaintenanceObserver
{
    /**
     * Handle the Maintenance "creating" event.
     */
    public function creating(Maintenance $maintenance): void
    {
        if (empty($maintenance->Status_ID)) {
            $maintenance->Status_ID = Status::firstOf(['Requested', 'New', 'Pending']) ?? 1;
        }
    }

    /**
     * Handle the Maintenance "saving" (creating or updating) event.
     * We use saving to check for completion_date.
     */
    public function saving(Maintenance $maintenance): void
    {
        if (!empty($maintenance->Completion_Date)) {
            $maintenance->Status_ID = Status::firstOf(['Completed', 'Closed', 'Resolved']) ?? $maintenance->Status_ID;
        }
    }

    /**
     * Handle the Maintenance "created" event.
     */
    public function created(Maintenance $maintenance): void
    {
        // When maintenance is created, set the asset status to "Out for Repair"
        $asset = $maintenance->asset;
        if ($asset) {
            // Only set to Out for Repair if it's not already completed
            $completedStatusId = Status::firstOf(['Completed', 'Closed', 'Resolved']);
            if ($maintenance->Status_ID != $completedStatusId) {
                $statusId = Status::firstOf(['Out for Repair', 'Maintenance']);
                if ($statusId) {
                    $asset->update(['Status_ID' => $statusId]);
                }
            }
        }
    }

    /**
     * Handle the Maintenance "updated" event.
     */
    public function updated(Maintenance $maintenance): void
    {
        if ($maintenance->isDirty('Status_ID')) {
            $completedStatusId = Status::firstOf(['Completed', 'Closed', 'Resolved']);
            $cancelledStatusId = Status::firstOf(['Cancelled', 'Rejected']);
            
            $newStatusId = $maintenance->Status_ID;

            if ($newStatusId == $completedStatusId || $newStatusId == $cancelledStatusId) {
                $asset = $maintenance->asset;
                if ($asset) {
                    // Decide whether it should be "Deployed" or "Ready to Deploy"
                    if ($asset->Employee_ID) {
                        $statusId = Status::firstOf(['Deployed', 'Assigned', 'In Use']);
                    } else {
                        $statusId = Status::firstOf(['Ready to Deploy', 'Available', 'Ready']);
                    }
                    
                    if ($statusId) {
                        $asset->update(['Status_ID' => $statusId]);
                    }
                }
            }
        }
    }
}
