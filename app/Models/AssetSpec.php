<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetSpec extends Model
{
    protected $fillable = [
        'asset_id',
        'processor',
        'memory',
        'storage_type',
        'storage_capacity',
        'operating_system',
        'mac_address',
        'ip_address'
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}