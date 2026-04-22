<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Accessory extends Model
{
    // Added the missing semicolon and included SoftDeletes since you imported it
    use HasFactory, SoftDeletes;
   
    protected $table = 'accessories';

    protected $fillable = [
        'name',
        'category',
        'model_number',
        'serial_no',
        'total_qty',
        'remaining_qty',
        'price', 
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_qty' => 'integer',
        'remaining_qty' => 'integer',
    ];
}