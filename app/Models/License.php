<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_key',
        'manufacturer',
        'total_seats',
        'remaining_seats',
        'price'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_seats' => 'integer',
        'remaining_seats' => 'integer',
    ];
}