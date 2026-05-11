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
     * Accessor for fields attribute.
     * Normalizes old 'key' property to 'name' for backward compatibility.
     */
    public function getFieldsAttribute($value)
    {
        $fields = is_array($value) ? $value : (json_decode($value, true) ?: []);
        foreach ($fields as &$field) {
            if (isset($field['key']) && !isset($field['name'])) {
                $field['name'] = $field['key'];
                unset($field['key']);
            }
        }
        return $fields;
    }

    /**
     * The "booted" method of the model.
     * Automatically generates a slug when creating a category.
     */
    
}