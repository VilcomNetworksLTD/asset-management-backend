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
        Schema::table('return_requests', function (Blueprint $table) {
            $table->json('Items')->nullable()->after('Missing_Items');
            // allow requests that reference only items rather than an asset
            $table->unsignedBigInteger('Asset_ID')->nullable()->change();
        });

        Schema::table('transfers', function (Blueprint $table) {
            // also make Asset_ID optional for the same reason
            $table->unsignedBigInteger('Asset_ID')->nullable()->change();

            // transfers already had Missing_Items column created by later migrations?
            // safe to add after it if present, otherwise append at end.
            if (!Schema::hasColumn('transfers', 'Missing_Items')) {
                $table->json('Items')->nullable();
            } else {
                $table->json('Items')->nullable()->after('Missing_Items');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropColumn('Items');
            // bring column back to required
            $table->unsignedBigInteger('Asset_ID')->nullable(false)->change();
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn('Items');
            $table->unsignedBigInteger('Asset_ID')->nullable(false)->change();
        });
    }
};
