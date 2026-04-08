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
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            }
            // Stores the actual values for the dynamic fields (e.g., {"ram": "16GB"})
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
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('custom_attributes');
            // Only drop foreign key if we added it in this migration, 
            // but usually safe to leave or handle manually if rolling back partial changes.
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};