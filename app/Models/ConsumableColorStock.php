<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumableColorStock extends Model
{
    use HasFactory;

    protected $table = 'consumable_color_stocks';

    protected $fillable = [
        'consumable_id',
        'color',
        'in_stock',
        'min_amt',
    ];

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class);
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
}
