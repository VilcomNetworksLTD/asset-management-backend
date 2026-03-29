<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetSpecification extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function specificationable()
    {
        return $this->morphTo();
    }
}