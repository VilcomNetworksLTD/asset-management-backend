<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne; // Update this import

class AssetSpec extends Model
{
    protected $fillable = [
        // 'asset_id', <-- You can actually remove this from fillable now, 
        // as the linking happens in the polymorphic pivot table, not here anymore!
        'processor',
        'memory',
        'storage_type',
        'storage_capacity',
        'operating_system',
        'mac_address',
        'ip_address'
    ];

    /**
     * Replaces the old asset() belongsTo relationship.
     * This links your existing IT specs to the new dynamic central table.
     */
    public function assetSpecification(): MorphOne
    {
        return $this->morphOne(AssetSpecification::class, 'specificationable');
    }
}