<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Issue;
use Illuminate\Support\Facades\DB;

class TicketService
{
    /**
     * Resolve a ticket and its parent issue simultaneously.
     */
    public function resolveTicket($ticketId, $communication, $statusId)
    {
        return DB::transaction(function () use ($ticketId, $communication, $statusId) {
            // 1. Find and update the Ticket
            $ticket = Ticket::findOrFail($ticketId);
            $ticket->update([
                'Communication_log' => $communication,
                'Status_ID' => $statusId,
            ]);

            // 2. Find and update the parent Issue linked to this ticket
            // Using your Issue_ID foreign key from the Ticket model
            if ($ticket->Issue_ID) {
                $issue = Issue::find($ticket->Issue_ID);
                if ($issue) {
                    $issue->update([
                        'Status_ID' => $statusId,
                    ]);
                }
            }

            return $ticket;
        });
    }

    /**
     * Create a new ticket from an issue (Useful for user-reporting flow).
     */
    public function createTicketFromIssue(Issue $issue, $priority = 'low')
    {
        return Ticket::create([
            'Employee_ID' => $issue->Employee_ID,
            'Issue_ID'    => $issue->id,
            'Status_ID'   => $issue->Status_ID,
            'Priority'    => $priority,
            'Description' => $issue->Issue_Description,
        ]);
    }
}