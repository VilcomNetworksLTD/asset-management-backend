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

            // Add foreign keys to new tables
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');

            // Add the new barcode column
            $table->string('barcode')->unique()->nullable(); // unique is key!
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Drop the foreign keys and columns we added
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');

            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');

            $table->dropColumn('barcode');

            // Re-add the old column (adjust type/modifiers if it wasn't a nullable string originally)
            $table->string('category')->nullable();
        });
    }
};