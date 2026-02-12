<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_verified',
        'otp_code',
        'otp_expires_at',
        'reset_otp',
        'reset_otp_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
        'reset_otp',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'otp_expires_at' => 'datetime',
        'reset_otp_expires_at' => 'datetime',
    ];

    /*
     ERD RELATIONSHIPS
    */
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

   
    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class, 'Employee_ID', 'id');
    }
}