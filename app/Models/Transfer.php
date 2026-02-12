<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    use HasFactory;

    
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

    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Employee_ID', 'id');
    }
}