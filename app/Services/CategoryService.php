<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * Get all categories sorted alphabetically.
     */
    public function getAllCategories()
    {
        return Category::orderBy('name', 'asc')->get();
    }

    /**
     * Create a new category and handle the slug generation.
     */
    public function createCategory(array $data)
    {
        // Automatically generate a URL-friendly slug
        $data['slug'] = Str::slug($data['name']);
        
        return Category::create($data);
    }
}