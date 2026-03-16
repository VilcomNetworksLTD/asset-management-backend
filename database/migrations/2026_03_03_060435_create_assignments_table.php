<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Asset_ID');
            $table->unsignedBigInteger('Employee_ID'); 
            $table->date('assignment_date');
            $table->date('return_date')->nullable();
            $table->timestamps();

            $table->foreign('Asset_ID')->references('id')->on('assets')->onDelete('cascade');
            
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};