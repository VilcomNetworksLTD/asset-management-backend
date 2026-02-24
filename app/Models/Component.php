<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    use HasFactory;

    // This matches the table name in your database
    protected $table = 'components';

    // These match the columns I see in your structure
    protected $fillable = [
        'name', 
        'category', 
        'serial_no', 
        'total_qty', 
        'remaining_qty', 
        'price'
    ];
}