<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_asset_specifications', function (Blueprint $table) {
            $table->id();
            $table->string('make')->nullable(); // e.g., Toyota
            $table->string('model')->nullable(); // e.g., Hilux
            $table->string('license_plate')->unique()->nullable();
            $table->year('manufacture_year')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_asset_specifications');
    }
};