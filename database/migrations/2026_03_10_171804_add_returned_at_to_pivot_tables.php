<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    if (!Schema::hasColumn('accessory_user', 'returned_at')) {
        Schema::table('accessory_user', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('quantity');
        });
    }

    if (!Schema::hasColumn('consumable_user', 'returned_at')) {
        Schema::table('consumable_user', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('quantity');
        });
    }
    
    // Add it to any other pivot tables (like license_user) if needed
}

public function down(): void
{
    Schema::table('accessory_user', function (Blueprint $table) {
        $table->dropColumn('returned_at');
    });
    Schema::table('consumable_user', function (Blueprint $table) {
        $table->dropColumn('returned_at');
    });
}
};
