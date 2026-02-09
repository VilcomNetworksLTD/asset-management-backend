<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_name',
        'asset_category',
        'serial_no',
        'supplier_id',
        'employee_id',
        'status_id',
        'price',
        'warranty_details',
        'license_info'
    ];
}
