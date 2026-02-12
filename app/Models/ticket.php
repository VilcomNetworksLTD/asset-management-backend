<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'Employee_ID',
        'Issue_ID',
        'Status_ID',
        'Priority',
        'Description',
        'Timestamp',
        'Communication_log',
    ];

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
}