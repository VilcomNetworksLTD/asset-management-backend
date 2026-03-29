<?php

namespace App\Observers;

use App\Models\ReturnRequest;
use App\Models\Status;

class ReturnRequestObserver
{
    /**
     * Handle the ReturnRequest "creating" event.
     */
    public function creating(ReturnRequest $returnRequest): void
    {
        if (empty($returnRequest->Status_ID)) {
            $returnRequest->Status_ID = Status::firstOf(['Pending', 'New', 'Open']) ?? 1;
        }
    }
}
