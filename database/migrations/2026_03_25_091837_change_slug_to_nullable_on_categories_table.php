<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // This tells MySQL: "Keep the column, but stop requiring a value"
            $table->string('slug')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // This reverts it back to required if you ever roll back
            $table->string('slug')->nullable(false)->change();
        });
    }
};