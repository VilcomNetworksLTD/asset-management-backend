<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'Supplier_Name',
        'Location',
        'Contact',
    ];

    /*RELATIONSHIPS  */

    
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'Supplier_ID', 'id');
    }
}