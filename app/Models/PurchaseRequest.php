<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'item_name',
        'description',
        'status',
        'estimated_cost',
        'rejection_reason',
        'management_id',
        'ticket_id',
        'maintenance_id',
        'notes',
        'approved_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['asset_owner_name', 'source_type_label'];

    /**
     * The user who requested the purchase.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The management user who approved/rejected the purchase.
     */
    public function management(): BelongsTo
    {
        return $this->belongsTo(User::class, 'management_id');
    }

    /**
     * Alias for management (for backward compatibility).
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'management_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class, 'maintenance_id');
    }

    /**
     * Get the asset owner name for the purchase request.
     * For maintenance requests, returns 'Unassigned' (since maintenance happens after asset is unassigned).
     * For ticket requests, returns the requester's name (the person who requested the asset).
     * For direct requests, returns the requester's name.
     */
    public function getAssetOwnerNameAttribute(): ?string
    {
        // For maintenance-related requests, return 'Unassigned' since maintenance happens after asset is unassigned
        if ($this->maintenance_id) {
            return 'Unassigned';
        }

        // For ticket-related requests, return the requester's name (the person who requested the asset)
        if ($this->ticket_id) {
            if ($this->requester) {
                return $this->requester->name;
            }
        }

        // For direct requests, return the requester's name
        if ($this->requester) {
            return $this->requester->name;
        }

        return null;
    }

    /**
     * Get the source type label for display.
     */
    public function getSourceTypeLabelAttribute(): string
    {
        if ($this->ticket_id) {
            return 'Ticket';
        }
        if ($this->maintenance_id) {
            return 'Maintenance';
        }
        return 'Direct Request';
    }
}
