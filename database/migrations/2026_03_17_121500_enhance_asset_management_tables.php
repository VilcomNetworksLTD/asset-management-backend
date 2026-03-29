<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add system_name and system_manufacturer to assets table
        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                if (!Schema::hasColumn('assets', 'system_name')) {
                    $table->string('system_name')->nullable()->after('Asset_Name');
                }
                if (!Schema::hasColumn('assets', 'system_manufacturer')) {
                    $table->string('system_manufacturer')->nullable()->after('system_name');
                }
            });
        }

        // 2. Add reason to transfers table
        if (Schema::hasTable('transfers')) {
            Schema::table('transfers', function (Blueprint $table) {
                if (!Schema::hasColumn('transfers', 'reason')) {
                    $table->text('reason')->nullable()->after('Notes');
                }
            });
        }

        // 3. Add reason to return_requests table
        if (Schema::hasTable('return_requests')) {
            Schema::table('return_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('return_requests', 'reason')) {
                    $table->text('reason')->nullable()->after('Notes');
                }
            });
        }

        // 4. Add color to consumables table
        if (Schema::hasTable('consumables')) {
            Schema::table('consumables', function (Blueprint $table) {
                if (!Schema::hasColumn('consumables', 'color')) {
                    $table->string('color')->nullable()->after('category');
                }
            });
        }

        // 5. Rename 'Desktop' category to 'Desktop CPU'
        DB::table('assets')
            ->where('Asset_Category', 'Desktop')
            ->update(['Asset_Category' => 'Desktop CPU']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropColumn(['system_name', 'system_manufacturer']);
            });
        }

        if (Schema::hasTable('transfers')) {
            Schema::table('transfers', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }

        if (Schema::hasTable('return_requests')) {
            Schema::table('return_requests', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }

        if (Schema::hasTable('consumables')) {
            Schema::table('consumables', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }

        // Optional: Revert category name (be careful if 'Desktop CPU' was already used)
        DB::table('assets')
            ->where('Asset_Category', 'Desktop CPU')
            ->update(['Asset_Category' => 'Desktop']);
    }
};
