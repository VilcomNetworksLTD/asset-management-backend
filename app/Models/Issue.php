<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $fillable = [
        'employee_id',
        'asset_id',
        'ticket_id',
        'status_id',
    ];
}
