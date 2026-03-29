<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\Status;

class TicketObserver
{
    /**
     * Handle the Ticket "creating" event.
     */
    public function creating(Ticket $ticket): void
    {
        if (empty($ticket->Status_ID)) {
            $ticket->Status_ID = Status::firstOf(['New', 'Pending', 'Open']) ?? 1;
        }
    }
}
