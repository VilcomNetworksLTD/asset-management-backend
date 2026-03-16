<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory, SoftDeletes;

    
    protected $table = 'feedback';

    protected $fillable = [
        'Asset_ID',
        'Employee_ID',
        'Comments',
        'Timestamp', 
    ];

    /*
    RELATIONSHIPS
    */

    
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'Asset_ID', 'id');
    }

    
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Employee_ID', 'id');
    }
    
}