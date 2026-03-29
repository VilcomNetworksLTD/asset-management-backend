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
        Schema::create('it_asset_specifications', function (Blueprint $table) {
            $table->id();
            
            // Your exact IT fields (notice there is NO asset_id here, 
            // because the polymorphic pivot table handles the linking now!)
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_asset_specifications');
    }
};