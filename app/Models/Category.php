<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; // Import the Str helper

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'fields',
        'slug', // 1. Added slug to fillable
        'created_by',
    ];

    protected $casts = [
        'fields' => 'array',
    ];

    /**
     * The "booted" method of the model.
     * Automatically generates a slug when creating a category.
     */
    
}