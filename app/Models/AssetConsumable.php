<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetConsumable extends Model
{
    protected $table = 'asset_consumables';

    protected $fillable = [
        'asset_id', 
        'consumable_id', 
        'color', 
        'installed_at', 
        'depleted_at', 
        'installed_by'
    ];

    public function asset(): BelongsTo
    {
        // Links 'asset_id' to 'id' on the 'assets' table
        return $this->belongsTo(Asset::class, 'asset_id', 'id');
    }

    public function consumable(): BelongsTo
    {
        // Links 'consumable_id' to 'id' on the 'consumables' table
        return $this->belongsTo(Consumable::class, 'consumable_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by', 'id');
    }
}