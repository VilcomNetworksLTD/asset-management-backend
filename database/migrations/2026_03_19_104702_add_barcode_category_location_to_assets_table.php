<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('assets', function (Blueprint $table) {
        if (!Schema::hasColumn('assets', 'barcode')) {
            $table->string('barcode')->unique()->nullable()->after('id'); // Stores VNL00001
        }
        
        // Ensure these columns exist and are foreign keys
        // If you already have them, ensure they point to the new tables
        if (!Schema::hasColumn('assets', 'category_id')) {
            $table->foreignId('category_id')->constrained('categories');
        }
        if (!Schema::hasColumn('assets', 'location_id')) {
            $table->foreignId('location_id')->constrained('locations');
        }
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            //
        });
    }
};
