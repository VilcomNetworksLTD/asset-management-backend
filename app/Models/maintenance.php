<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    use HasFactory;


     protected $table = 'maintenance';

    
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
    ];

    
    protected $casts = [
        'Request_Date' => 'datetime',
        'Completion_Date' => 'datetime',
        'Cost' => 'decimal:2',
    ];

    /*RELATIONSHIPS */

   
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'Asset_ID', 'id');
    }

   
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'Ticket_ID', 'id');
    }
}