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
            $maintenance->Status_ID = Status::whereRaw("LOWER(Status_Name) IN ('requested', 'new', 'pending', 'out for repair')")->value('id') ?? 1;
        }
    }

    /**
     * Handle the Maintenance "saving" (creating or updating) event.
     * We use saving to check for completion_date.
     */
    public function saving(Maintenance $maintenance): void
    {
        if (!empty($maintenance->Completion_Date)) {
            $maintenance->Status_ID = Status::whereRaw("LOWER(Status_Name) IN ('completed', 'closed', 'resolved')")->value('id') ?? $maintenance->Status_ID;
        }
    }

    /**
     * Handle the Maintenance "created" event.
     */
    public function created(Maintenance $maintenance): void
    {
        // Log maintenance created in ActivityLog
        $user = \Illuminate\Support\Facades\Auth::user();
        \App\Models\ActivityLog::create([
            'asset_id' => $maintenance->Asset_ID,
            'Employee_ID' => $user ? $user->id : $maintenance->Actioned_By,
            'user_name' => $user ? $user->name : 'System',
            'action' => 'Maintenance Logged',
            'target_type' => 'Maintenance',
            'target_name' => $maintenance->Maintenance_Type ?? 'Routine Maintenance',
            'details' => "Maintenance request created for linked Asset ID: {$maintenance->Asset_ID}"
        ]);

        // When maintenance is created, set the asset status to "Out for Repair"
        $asset = $maintenance->asset;
        if ($asset) {
            // Only set to Out for Repair if it's not already completed
            $completedStatusId = Status::whereRaw("LOWER(Status_Name) IN ('completed', 'closed', 'resolved')")->value('id');
            if ($maintenance->Status_ID != $completedStatusId) {
                $statusId = Status::whereRaw("LOWER(Status_Name) IN ('out for repair', 'maintenance')")->value('id');
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
            $status = Status::find($maintenance->Status_ID);
            $statusName = $status ? strtolower($status->Status_Name) : '';

            // Determine action label for the audit log
            $actionMap = [
                'completed'    => 'Maintenance Completed',
                'closed'       => 'Maintenance Completed',
                'resolved'     => 'Maintenance Completed',
                'solved'       => 'Maintenance Completed',
                'cancelled'    => 'Maintenance Cancelled',
                'in progress'  => 'Maintenance In Progress',
                'on hold'      => 'Maintenance On Hold',
                'out for repair'  => 'Asset Sent for Repair',
                'under repair'    => 'Asset Under Repair',
                'scheduled'       => 'Maintenance Scheduled',
                'archived'        => 'Asset Archived',
            ];
            $action = $actionMap[$statusName] ?? 'Maintenance Status Updated';

            $user = \Illuminate\Support\Facades\Auth::user();
            \App\Models\ActivityLog::create([
                'asset_id'    => $maintenance->Asset_ID,
                'Employee_ID' => $user ? $user->id : $maintenance->Actioned_By,
                'user_name'   => $user ? $user->name : 'System',
                'action'      => $action,
                'target_type' => 'Maintenance',
                'target_name' => $maintenance->Maintenance_Type ?? 'Routine Maintenance',
                'details'     => "Maintenance status updated to: " . ($status ? $status->Status_Name : $maintenance->Status_ID),
            ]);

            $asset = $maintenance->asset;
            if ($asset) {
                // Terminal states: restore asset to Available / Deployed
                $terminalStatuses = ['completed', 'closed', 'resolved', 'solved', 'cancelled', 'rejected', 'archived'];

                if (in_array($statusName, $terminalStatuses)) {
                    if ($statusName === 'archived') {
                        $newStatusId = Status::firstOf(['Archived', 'Disposed', 'Retired']);
                    } elseif ($asset->Employee_ID) {
                        $newStatusId = Status::firstOf(['Deployed', 'Assigned', 'In Use']);
                    } else {
                        $newStatusId = Status::firstOf(['Available', 'Ready to Deploy', 'Ready']);
                    }
                } else {
                    // All active repair / maintenance workflow states keep asset as "Out for Repair"
                    $newStatusId = Status::whereRaw("LOWER(Status_Name) IN ('out for repair', 'under repair', 'maintenance')")
                        ->orderByRaw("CASE WHEN LOWER(Status_Name) = 'out for repair' THEN 1 WHEN LOWER(Status_Name) = 'under repair' THEN 2 ELSE 3 END")
                        ->value('id');
                }

                if ($newStatusId) {
                    $asset->update(['Status_ID' => $newStatusId]);
                }
            }
        }
    }
}
