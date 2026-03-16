<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnRequest extends Model
{
    use SoftDeletes;

    // 1. Define workflow statuses as constants for consistency and to avoid magic strings.
    public const WORKFLOW_PENDING_APPROVAL = 'Pending Approval';
    public const WORKFLOW_APPROVED = 'Approved - Awaiting Return';
    public const WORKFLOW_REJECTED = 'Rejected';
    public const WORKFLOW_RETURNED = 'Returned - Under Inspection';
    public const WORKFLOW_COMPLETED = 'Completed';
    public const WORKFLOW_COMPLETED_WITH_ISSUES = 'Completed with Issues';
    public const WORKFLOW_CANCELLED = 'Cancelled';



    protected $fillable = [
        'Asset_ID',
        'Employee_ID',
        'Sender_ID',
        'Status_ID',
        'Request_Date',
        'Workflow_Status',
        'Sender_Condition',
        'Admin_Condition',
        'Missing_Items',
        'Items',          // new: other item types
        'Notes',
        'Actioned_By',
        'Actioned_At',
        'Ticket_ID',
    ];

    protected $casts = [
        'Request_Date' => 'datetime',
        'Actioned_At' => 'datetime',
        'Missing_Items' => 'array',
        'Items' => 'array',
    ];

    /**
     * 2. Use a model event to set the initial status automatically when a request is created.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($returnRequest) {
            // Set initial workflow status if it's not already set
            if (empty($returnRequest->Workflow_Status)) {
                $returnRequest->Workflow_Status = self::WORKFLOW_PENDING_APPROVAL;
            }

            // Find and set the corresponding Status_ID from the statuses table
            // This keeps Status_ID and Workflow_Status in sync.
            $statusModel = Status::where('Status_Name', $returnRequest->Workflow_Status)->first();
            if ($statusModel) {
                $returnRequest->Status_ID = $statusModel->id;
            }
        });
    }

    /**
     * 3. Create a centralized method for all state transitions.
     * This ensures all status changes are handled consistently.
     *
     * @param string $newStatus The new status from the constants above.
     * @param User|null $actionedBy The user performing the action.
     * @param array $data Additional data to update on the model.
     * @return $this
     */
    public function transitionTo(string $newStatus, ?User $actionedBy = null, array $data = []): self
    {
        $this->Workflow_Status = $newStatus;

        // Find and set the corresponding Status_ID
        $statusModel = Status::where('Status_Name', $newStatus)->first();
        if ($statusModel) {
            $this->Status_ID = $statusModel->id;
        }

        // Update action fields
        $this->Actioned_By = $actionedBy ? $actionedBy->id : null;
        $this->Actioned_At = now();

        // Update other fields from the $data array
        $this->fill($data);

        $this->save();

        return $this;
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'Asset_ID', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Employee_ID', 'id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Sender_ID', 'id');
    }

    public function actionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Actioned_By', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'Status_ID', 'id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'Ticket_ID', 'id');
    }
}
