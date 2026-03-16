<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'activity_logs'; 
        protected $fillable = [
        'asset_id',   
        'Employee_ID', // Now included for filtering
        'user_name', 
        'action', 
        'target_type', 
        'target_name', 
        'details'
    ];
    public function user()
    {
        
        return $this->belongsTo(User::class, 'Employee_ID');
    }
    public function asset()
{
    return $this->belongsTo(Asset::class);
}
}