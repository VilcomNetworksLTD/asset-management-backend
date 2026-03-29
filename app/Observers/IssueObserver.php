<?php

namespace App\Observers;

use App\Models\Issue;
use App\Models\Status;

class IssueObserver
{
    /**
     * Handle the Issue "creating" event.
     */
    public function creating(Issue $issue): void
    {
        if (empty($issue->Status_ID)) {
            $issue->Status_ID = Status::firstOf(['New', 'Open', 'Pending']) ?? 1;
        }
    }
}
