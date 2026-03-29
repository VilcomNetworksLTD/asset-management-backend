<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class VehicleAssetSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'make',
        'model',
        'license_plate',
        'manufacture_year'
    ];

    public function assetSpecification(): MorphOne
    {
        return $this->morphOne(AssetSpecification::class, 'specificationable');
    }
}