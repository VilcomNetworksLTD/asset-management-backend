<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'asset_id',
        'employee_id',
        'status_id',
        'communication_log',
    ];
}
