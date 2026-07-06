<?php

namespace App\Observers;

use App\Models\Asset;
use App\Models\Status;
use App\Models\User;
use App\Models\Accessory;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssetObserver
{
    /**
     * Handle the Asset "creating" event.
     */
    public function creating(Asset $asset): void
    {
        if (empty($asset->Status_ID)) {
            $asset->Status_ID = Status::firstOf(['Ready to Deploy', 'Available', 'Ready']) ?? 1;
            $asset->physical_condition = $asset->physical_condition ?? 'New';
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
                // Preserve protected statuses — do NOT reset these to Ready to Deploy
                $protectedIds = Status::whereIn('Status_Name', [
                    'Out for Repair', 'Maintenance', 'Under Repair',
                    'Non-Deployable', 'Retired', 'Broken', 'Lost', 'Stolen', 'Archived',
                ])->pluck('id')->toArray();

                if (!in_array($asset->Status_ID, $protectedIds)) {
                    $statusId = Status::firstOf(['Ready to Deploy', 'Available', 'Ready']);
                    if ($statusId) {
                        $asset->Status_ID = $statusId;
                    }
                }
            }

            // Custody transition logging & linked accessories recovery logic
            if ($oldEmployeeId !== $newEmployeeId) {
                $oldUser = $oldEmployeeId ? User::find($oldEmployeeId) : null;
                $newUser = $newEmployeeId ? User::find($newEmployeeId) : null;

                $fromName = $oldUser ? $oldUser->name : 'Unassigned';
                $toName = $newUser ? $newUser->name : 'Unassigned';

                $isFirstAssignment = empty($oldEmployeeId);
                $action = $isFirstAssignment ? 'Assigned' : "Custody Changed from: {$fromName} to: {$toName}";
                $adminName = Auth::user()->name ?? 'System';
                $details = $isFirstAssignment ? "Assigned to {$toName} by {$adminName}" : "Reassigned from {$fromName} to {$toName}";

                // Log custody hand-over details
                ActivityLog::create([
                    'asset_id' => $asset->id,
                    'Employee_ID' => Auth::id(),
                    'user_name' => Auth::user()->name ?? 'System',
                    'action' => $action,
                    'target_type' => 'Asset',
                    'target_name' => $asset->Asset_Name,
                    'details' => $details,
                ]);

                // If previously assigned to a person, return linked accessories back to stock
                if ($oldEmployeeId) {
                    $linkedAccessories = DB::table('accessory_user')
                        ->where('user_id', $oldEmployeeId)
                        ->where('asset_id', $asset->id)
                        ->whereNull('returned_at')
                        ->get();

                    foreach ($linkedAccessories as $pivotRow) {
                        // Mark as returned
                        DB::table('accessory_user')
                            ->where('id', $pivotRow->id)
                            ->update(['returned_at' => now()]);

                        // Return quantity back to stock
                        $accessory = Accessory::find($pivotRow->accessory_id);
                        if ($accessory) {
                            $accessory->increment('remaining_qty', $pivotRow->quantity);

                            // Log accessory return
                            ActivityLog::create([
                                'asset_id' => $asset->id,
                                'Employee_ID' => Auth::id(),
                                'user_name' => Auth::user()->name ?? 'System',
                                'action' => 'Returned Accessory',
                                'target_type' => 'Accessory',
                                'target_name' => $accessory->name,
                                'details' => "Accessory '{$accessory->name}' returned to stock (quantity: {$pivotRow->quantity}) due to asset reassignment from {$fromName}.",
                            ]);
                        }
                    }
                }
            }
        }
    }
}
