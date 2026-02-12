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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id(); 
            
            // Foreign Keys
            $table->foreignId('Employee_ID')->constrained('users')->onDelete('cascade');
            $table->foreignId('Issue_ID')->constrained('issues')->onDelete('cascade');
            $table->foreignId('Status_ID')->constrained('statuses')->onDelete('cascade');
            
            
            $table->foreignId('Asset_ID')->nullable()->constrained('assets')->onDelete('cascade');

           
            $table->string('Priority')->default('Medium'); // Low, Medium, High, Urgent
            $table->text('Description')->nullable();
            
            
            $table->longText('Communication_log')->nullable();

            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};