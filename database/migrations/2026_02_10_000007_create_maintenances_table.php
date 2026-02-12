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
        
        Schema::create('maintenance', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('Asset_ID')->constrained('assets')->onDelete('cascade');
            $table->foreignId('Ticket_ID')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('Status_ID')->constrained('statuses')->onDelete('cascade');

            // Maintenance Details
            $table->string('Maintenance_Type'); // e.g., Repair, Upgrade, Prevention
            $table->text('Description');
            $table->decimal('Cost', 10, 2)->default(0.00);
            

            $table->dateTime('Request_Date');
            $table->dateTime('Completion_Date')->nullable();
            $table->dateTime('Maintenance_Date')->nullable(); // Specific scheduled date

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance');
    }
};