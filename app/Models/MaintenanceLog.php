<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    protected $fillable = [
        'asset_id',
        'request_date',
        'completion_date',
        'maintenance_type',
        'description',
        'maintenance_date',
        'status',
    ];
}
