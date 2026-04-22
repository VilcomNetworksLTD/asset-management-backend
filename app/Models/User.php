<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'is_verified',
        'otp_code',
        'otp_expires_at',
        'reset_otp',
        'reset_otp_expires_at',
        'Status_ID',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
        'reset_otp',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'otp_expires_at' => 'datetime',
        'reset_otp_expires_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'Employee_ID', 'id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class, 'Employee_ID', 'id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'Employee_ID', 'id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'Employee_ID', 'id');
    }

    protected static function boot()
    {
        parent::boot();
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class, 'Employee_ID', 'id');
    }

    public function accessories()
    {
        return $this->belongsToMany(Accessory::class)->withPivot('quantity', 'returned_at')->withTimestamps();
    }

    public function consumables()
    {
        return $this->belongsToMany(Consumable::class)->withPivot('quantity', 'returned_at')->withTimestamps();
    }

    public function licenses()
    {
        return $this->belongsToMany(License::class)->withPivot('returned_at')->withTimestamps();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'Status_ID');
    }
}
