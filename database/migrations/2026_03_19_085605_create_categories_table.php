<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'IT Assets', 'Furniture'
            $table->string('slug')->nullable()->unique();
         // For potential clean URLs (e.g., 'it-assets')
            $table->string('description')->nullable(); // Optional description
            $table->boolean('requires_specific_fields')->default(false); // To track if a category needs specific form fields
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};