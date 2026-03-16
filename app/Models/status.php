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

    /**
     * Return the id for the first matching status name from a list.
     * Helps the rest of the app avoid hard‑coded numbers.
     */
    public static function firstOf(array $names): ?int
    {
        foreach ($names as $name) {
            $status = self::whereRaw('LOWER(Status_Name)=?', [strtolower($name)])->first();
            if ($status) {
                return (int)$status->id;
            }
        }
        return null;
    }
}