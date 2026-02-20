<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consumable extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * These match the columns in your UI and migration.
     */
    protected $fillable = [
        'item_name', // Maps to "ITEM NAME" in your UI
        'category',  // Maps to "CATEGORY" in your UI
        'in_stock',  // Maps to "IN STOCK" in your UI
        'price',     // Maps to "PRICE" in your UI
        'min_amt',   // Useful for low-stock alerts
    ];

    /**
     * Optional: Casting price to a float/decimal ensures 
     * correct math operations in your service.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'in_stock' => 'integer',
    ];
}