<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Status;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    public function creating(User $user): void
    {
        $activeStatus = Status::firstOrCreate(['Status_Name' => 'Active']);
        $user->Status_ID = $activeStatus->id;
    }

    /**
     * Handle the User "deleting" event.
     * Write the Deactivated status directly via DB so it is persisted
     * before the soft-delete timestamp is applied.
     */
    public function deleting(User $user): void
    {
        $deactivatedStatusId = Status::where('Status_Name', 'Deactivated')->value('id');
        if ($deactivatedStatusId && $user->id) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['Status_ID' => $deactivatedStatusId]);
        }
    }
}
