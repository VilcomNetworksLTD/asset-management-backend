<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs'; //

    protected $fillable = [
        'Employee_ID', // Now included for filtering
        'user_name', 
        'action', 
        'target_type', 
        'target_name', 
        'details'
    ];
}