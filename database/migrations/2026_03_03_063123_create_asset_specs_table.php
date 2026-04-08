<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->string('processor')->nullable();
            $table->string('memory')->nullable();
            $table->string('storage_type')->nullable();
            $table->string('storage_capacity')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_specs');
    }
};

