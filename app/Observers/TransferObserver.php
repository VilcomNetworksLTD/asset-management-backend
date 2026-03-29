<?php

namespace App\Observers;

use App\Models\Transfer;
use App\Models\Status;

class TransferObserver
{
    /**
     * Handle the Transfer "creating" event.
     */
    public function creating(Transfer $transfer): void
    {
        if (empty($transfer->Status_ID)) {
            $transfer->Status_ID = Status::firstOf(['Pending', 'New', 'Open']) ?? 1;
        }
    }
}
