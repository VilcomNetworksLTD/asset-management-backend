<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Status;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'Supplier_Name',
        'Location',
        'Contact',
        'Status_ID',
    ];

    protected static function boot()
    {
        parent::boot();
    }

    /*RELATIONSHIPS  */

    
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'Supplier_ID', 'id');
    }

    public function status(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Status::class, 'Status_ID');
    }
}