<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasFactory;

   
    protected $fillable = [
        'Status_Name',
    ];

    /*RELATIONSHIPS */

    
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'Status_ID', 'id');
    }

    
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'Status_ID', 'id');
    }

    
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class, 'Status_ID', 'id');
    }
}