<?php

namespace App\Observers;

use App\Models\Supplier;
use App\Models\Status;
use Illuminate\Support\Facades\DB;

class SupplierObserver
{
    public function creating(Supplier $supplier): void
    {
        $activeStatus = Status::firstOrCreate(['Status_Name' => 'Active']);
        $supplier->Status_ID = $activeStatus->id;
    }

    /**
     * Handle the Supplier "deleting" event.
     * Write the Deactivated status directly via DB so it is persisted
     * before the soft-delete timestamp is applied.
     */
    public function deleting(Supplier $supplier): void
    {
        $deactivatedStatusId = Status::where('Status_Name', 'Deactivated')->value('id');
        if ($deactivatedStatusId && $supplier->id) {
            DB::table('suppliers')
                ->where('id', $supplier->id)
                ->update(['Status_ID' => $deactivatedStatusId]);
        }
    }
}
