<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consumable extends Model
{
    use HasFactory, SoftDeletes;

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
        'min_amt' => 'integer',
    ];

    /**
     * Attributes to append to the JSON form.
     */
    protected $appends = ['status', 'total_value'];

    public function usageHistory(): HasMany
    {
        return $this->hasMany(AssetConsumable::class, 'consumable_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('quantity', 'returned_at')->withTimestamps();
    }

    public function getStatusAttribute(): string
    {
        if ($this->in_stock <= 0) {
            return 'Out of Stock';
        }
        if ($this->in_stock <= $this->min_amt) {
            return 'Low Stock';
        }
        return 'In Stock';
    }

    public function getTotalValueAttribute(): float
    {
        return (float) ($this->in_stock * $this->price);
    }
}