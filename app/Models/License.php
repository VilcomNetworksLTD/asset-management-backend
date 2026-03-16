<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
    'name',
    'product_key',
    'manufacturer',
    'total_seats',
    'remaining_seats',
    'price',
    'expiry_date',
    'department_id',
    'allocation_type',
    'renewal_type',
];

protected $casts = [
    'price' => 'decimal:2',
    'total_seats' => 'integer',
    'remaining_seats' => 'integer',
    'expiry_date' => 'date',
];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}