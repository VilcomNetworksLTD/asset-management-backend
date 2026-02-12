<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Issue extends Model
{
    use HasFactory;
    protected $fillable = [
        'Employee_ID',
        'Asset_ID',
        'Ticket_ID',
        'Issue_Description',
        'Status_ID',
        'Timestamp',
    ];

    /* RELATIONSHIPS */

    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Employee_ID', 'id');
    }

    
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'Asset_ID', 'id');
    }

    
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'Ticket_ID', 'id');
    }
}