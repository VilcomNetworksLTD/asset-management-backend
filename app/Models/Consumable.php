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
        'item_name', 
        'category',  
        'price',     
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected $appends = ['status', 'total_value'];

    public function colorStocks(): HasMany
    {
        return $this->hasMany(ConsumableColorStock::class, 'consumable_id');
    }

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
        $stocks = $this->colorStocks;
        if ($stocks->isEmpty()) {
            return 'No Colors';
        }

        if ($stocks->every(fn($s) => $s->in_stock <= 0)) {
            return 'Out of Stock';
        }

        if ($stocks->contains(fn($s) => $s->in_stock <= $s->min_amt)) {
            return 'Low Stock';
        }

        return 'In Stock';
    }

    public function getTotalValueAttribute(): float
    {
        $totalStock = $this->colorStocks->sum('in_stock');
        return (float) ($totalStock * $this->price);
    }
}