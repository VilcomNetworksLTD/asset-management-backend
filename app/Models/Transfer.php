<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// ADDED: For status automation
use App\Models\Status;
use App\Models\User;

class Transfer extends Model
{
    use HasFactory;

    // 1. Define workflow statuses as constants.
    public const WORKFLOW_PENDING_APPROVAL = 'Pending Approval';
    public const WORKFLOW_APPROVED = 'Approved';
    public const WORKFLOW_REJECTED = 'Rejected';
    public const WORKFLOW_IN_TRANSIT = 'In Transit';
    public const WORKFLOW_RECEIVED = 'Received';
    public const WORKFLOW_COMPLETED = 'Completed';
    public const WORKFLOW_COMPLETED_WITH_ISSUES = 'Completed with Issues';
    public const WORKFLOW_CANCELLED = 'Cancelled';

    protected $fillable = [
        'Asset_ID',
        'Employee_ID',
        'Sender_ID',
        'Receiver_ID',
        'Status_ID',
        'Transfer_Date',
        'Type',
        'Workflow_Status',
        'Sender_Condition',
        'Admin_Condition',
        'Included_Items',
        'Missing_Items',
        'Items',           // new
        'Notes',
        'Actioned_By',
        'Actioned_At',
        'Ticket_ID',
    ];

    protected $casts = [
        'Transfer_Date' => 'datetime',
        'Actioned_At' => 'datetime',
        'Included_Items' => 'array',
        'Missing_Items' => 'array',
        'Items' => 'array',
    ];

    /**
     * 2. Use a model event to set the initial status automatically when a transfer is created.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transfer) {
            // Set initial workflow status if it's not already set
            if (empty($transfer->Workflow_Status)) {
                $transfer->Workflow_Status = self::WORKFLOW_PENDING_APPROVAL;
            }

            // Find and set the corresponding Status_ID from the statuses table
            $statusModel = Status::where('Status_Name', $transfer->Workflow_Status)->first();
            if ($statusModel) {
                $transfer->Status_ID = $statusModel->id;
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

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Receiver_ID', 'id');
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
