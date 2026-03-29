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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('Status_ID')->nullable()->after('role');
            $table->foreign('Status_ID')->references('id')->on('statuses')->onDelete('set null');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->unsignedBigInteger('Status_ID')->nullable()->after('Contact');
            $table->foreign('Status_ID')->references('id')->on('statuses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['Status_ID']);
            $table->dropColumn('Status_ID');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['Status_ID']);
            $table->dropColumn('Status_ID');
        });
    }
};
