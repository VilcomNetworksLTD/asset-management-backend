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
        Schema::table('accessories', function (Blueprint $table) {
            // Add serial_number column and make both model_number and serial_number optional
            $table->string('serial_number')->nullable()->after('model_number');
            $table->string('model_number')->nullable()->change();
        });

        // Note: The 'components' table and logic are being retired. 
        // Ensure any existing component data is migrated to accessories before dropping tables.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accessories', function (Blueprint $table) {
            $table->dropColumn('serial_number');
            $table->string('model_number')->nullable(false)->change();
        });
    }
};
