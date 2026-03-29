<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// ADDED: For status automation
use App\Models\Status;
use App\Models\User;

class Maintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'maintenance';

    // 1. Define workflow statuses as constants.
    public const WORKFLOW_SCHEDULED = 'Scheduled';
    public const WORKFLOW_IN_PROGRESS = 'In Progress';
    public const WORKFLOW_ON_HOLD = 'On Hold';
    public const WORKFLOW_COMPLETED = 'Completed';
    public const WORKFLOW_CANCELLED = 'Cancelled';
    public const WORKFLOW_OUT_FOR_REPAIR = 'Out for Repair';
    public const WORKFLOW_UNDER_REPAIR = 'Under Repair';
    public const WORKFLOW_ARCHIVED = 'Archived';

    protected $fillable = [
        'Asset_ID',         
        'Ticket_ID',        
        'Request_Date',     
        'Completion_Date',  
        'Maintenance_Type', 
        'Description',      
        'Cost',             
        'Status_ID',        
        'Maintenance_Date',
        // Added for status automation pattern
        'Workflow_Status',
        'Actioned_By',
        'Actioned_At',
    ];

    protected $casts = [
        'Request_Date' => 'date',
        'Completion_Date' => 'date',
        'Maintenance_Date' => 'date',
        'Actioned_At' => 'datetime',
    ];

    /**
     * 2. Use a model event to set the initial status automatically.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($maintenance) {
            // Set initial workflow status if it's not already set
            if (empty($maintenance->Workflow_Status)) {
                $maintenance->Workflow_Status = self::WORKFLOW_UNDER_REPAIR;
            }

            // Find and set the corresponding Status_ID from the statuses table
            $statusModel = Status::where('Status_Name', $maintenance->Workflow_Status)->first();
            if ($statusModel) {
                $maintenance->Status_ID = $statusModel->id;
            }

            // Automate Asset Status update to "Under Repair"
            if ($maintenance->Asset_ID) {
                $repairStatus = Status::where('Status_Name', self::WORKFLOW_UNDER_REPAIR)->first();
                if ($repairStatus) {
                    Asset::where('id', $maintenance->Asset_ID)->update(['Status_ID' => $repairStatus->id]);
                }
            }
        });
    }

    /**
     * 3. Create a centralized method for all state transitions.
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

        // Automate Asset Status updates based on Maintenance transition
        if ($this->Asset_ID) {
            $assetStatusName = null;
            if ($newStatus === self::WORKFLOW_COMPLETED) {
                $assetStatusName = 'Ready to Deploy';
            } elseif ($newStatus === self::WORKFLOW_CANCELLED) {
                $assetStatusName = 'Available';
            } elseif ($newStatus === self::WORKFLOW_ARCHIVED) {
                $assetStatusName = 'Archived';
            }

            if ($assetStatusName) {
                $assetStatus = Status::where('Status_Name', $assetStatusName)->first();
                if ($assetStatus) {
                    Asset::where('id', $this->Asset_ID)->update(['Status_ID' => $assetStatus->id]);
                }
            }
        }

        // Update action fields
        $this->Actioned_By = $actionedBy ? $actionedBy->id : null;
        $this->Actioned_At = now();

        // Update other fields from the $data array
        $this->fill($data);

        $this->save();

        return $this;
    }
    
    public function asset() {
        return $this->belongsTo(Asset::class, 'Asset_ID');
    }

    public function status() {
        return $this->belongsTo(Status::class, 'Status_ID');
    }

    public function actionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Actioned_By', 'id');
    }
}