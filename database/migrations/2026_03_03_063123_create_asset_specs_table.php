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
            
            
            $table->unsignedBigInteger('asset_id')->unique(); 
            
            
            $table->string('processor')->nullable();
            $table->string('memory')->nullable(); // e.g., 16GB
            $table->string('storage_type')->nullable(); // e.g., SSD/HDD
            $table->string('storage_capacity')->nullable(); // e.g., 512GB
            $table->string('operating_system')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('ip_address')->nullable();
            
            $table->timestamps();

            
            $table->foreign('asset_id')
                ->references('id')
                ->on('assets')
                ->onDelete('cascade');
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('asset_specs');
    }
};