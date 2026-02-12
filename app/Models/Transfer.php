<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    use HasFactory;

    /**
     * Attributes from ERD:
     * - Transfer_ID (Primary Key)
     * - Asset_ID: The piece of equipment being moved.
     * - Employee_ID: The person receiving the asset.
     * - Transfer_Date: When the hand-off happened.
     * - Status: The state of the transfer (e.g., 'Pending', 'Completed', 'Rejected').
     */
    protected $fillable = [
        'Asset_ID',
        'Employee_ID',
        'Timestamp',
        'Status_ID',
    ];

    
    protected $casts = [
        'Transfer_Date' => 'datetime',
    ];

    /* RELATIONSHIPS */

    
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'Asset_ID', 'id');
    }

    /**
     * Relationship: Transfer belongs to an Employee (The Recipient).
     * Relevance: Shows who is taking over responsibility for the equipment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Employee_ID', 'id');
    }
}