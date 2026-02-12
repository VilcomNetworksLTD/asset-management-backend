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
        Schema::create('issues', function (Blueprint $table) {
            $table->id(); // Primary Key
            
            // Foreign Keys linking to Employee and Asset
            $table->foreignId('Employee_ID')->constrained('users')->onDelete('cascade');
            $table->foreignId('Asset_ID')->constrained('assets')->onDelete('cascade');
            $table->foreignId('Status_ID')->constrained('statuses')->onDelete('cascade');

           
            $table->foreignId('Ticket_ID')->nullable()->constrained('tickets')->onDelete('set null');

            
            $table->text('Issue_Description');

            
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};