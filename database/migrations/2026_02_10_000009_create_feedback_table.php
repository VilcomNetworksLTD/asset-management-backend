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
        // Matches the $table = 'feedback' property in your model
        Schema::create('feedback', function (Blueprint $table) {
            $table->id(); // Primary Key
            
           
            $table->foreignId('Asset_ID')->constrained('assets')->onDelete('cascade');
            $table->foreignId('Employee_ID')->constrained('users')->onDelete('cascade');
            
            
            $table->text('Comments');
            
           
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};