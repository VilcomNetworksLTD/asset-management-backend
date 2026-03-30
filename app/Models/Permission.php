<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permissions extends Model
{
    use HasFactory;

    
    protected $table = 'permissions';

    
    protected $fillable = [
        'Employee_ID',
        'Role',
        'Permission_Level',
        'Module',
    ];

    /* RELATIONSHIPS */

    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Employee_ID', 'id');
    }
}