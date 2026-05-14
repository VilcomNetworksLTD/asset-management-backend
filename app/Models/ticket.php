<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class Ticket extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'Employee_ID',
        'Issue_ID',
        'Status_ID',
        'Priority',
        'Description',

        'Timestamp',
        'Communication_log',
        'rejection_reason',
    ];

    protected $appends = ['can_edit'];

    /*RELATIONSHIPS*/

    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Employee_ID', 'id');
    }

   
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'Issue_ID', 'id');
    }

    
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'Status_ID', 'id');
    }

    
    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'Ticket_ID', 'id');
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class, 'Ticket_ID', 'id');
    }

    public function transferRequests(): HasMany
    {
        return $this->hasMany(Transfer::class, 'Ticket_ID', 'id');
    }

    /**
     * Determine if the ticket can still be edited by the user.
     * @return bool
     */
    public function getCanEditAttribute(): bool
    {
        $statusName = strtolower($this->status?->Status_Name ?? 'pending');
        $actionableStatuses = ['pending', 'new', 'open'];
        return in_array($statusName, $actionableStatuses);
    }
}