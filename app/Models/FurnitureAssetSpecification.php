<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class FurnitureAssetSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'material',
        'dimensions',
        'color'
    ];

    public function assetSpecification(): MorphOne
    {
        return $this->morphOne(AssetSpecification::class, 'specificationable');
    }
}