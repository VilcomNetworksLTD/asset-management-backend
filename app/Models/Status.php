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
     * Get the first status ID matching any of the given names.
     *
     * @param array $names
     * @return int|null
     */
    public static function firstOf(array $names): ?int
    {
        foreach ($names as $name) {
            $status = self::where('Status_Name', $name)->first();
            if ($status) {
                return $status->id;
            }
        }
        return null;
    }
}
