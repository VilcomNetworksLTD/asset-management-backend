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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id(); 
            
            // Foreign Keys
            $table->foreignId('Asset_ID')->constrained('assets')->onDelete('cascade');
            $table->foreignId('Employee_ID')->constrained('users')->onDelete('cascade');
            $table->foreignId('Status_ID')->constrained('statuses')->onDelete('cascade');

            
            $table->dateTime('Transfer_Date')->useCurrent();

            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};