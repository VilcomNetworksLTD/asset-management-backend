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
        // 1. Ensure 'description' column exists first (needed for the 'after' clause below)
        if (Schema::hasTable('categories') && !Schema::hasColumn('categories', 'description')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->text('description')->nullable();
            });
        }

        // 2. Ensure 'fields' column exists
        if (Schema::hasTable('categories') && !Schema::hasColumn('categories', 'fields')) {
            Schema::table('categories', function (Blueprint $table) {
                // We can safely use after('description') now that we ensured it exists
                $table->json('fields')->nullable()->after('description');
            });
        }

        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'custom_attributes')) {
                $table->json('custom_attributes')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed for down as we are just ensuring columns exist
    }
};