<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accessory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * (Optional if your table is named 'accessories' as Laravel assumes this)
     */
    protected $table = 'accessories';

    /**
     * The attributes that are mass assignable.
     * These match the columns defined in your migration up() method.
     */
    protected $fillable = [
        'name',
        'category',
        'model_number',
        'total_qty',
        'remaining_qty',
        'price', // Matches the decimal(10,2) format
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'total_qty' => 'integer',
        'remaining_qty' => 'integer',
    ];
}