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
        Schema::create('assets', function (Blueprint $table) {
            $table->id(); // This is the primary key (referenced as Asset_ID elsewhere)
            
            $table->string('Asset_Name');
            $table->string('Asset_Category');
            $table->string('Serial_No')->unique();
            $table->text('Warranty_Details')->nullable();
            $table->text('License_Info')->nullable();
            $table->decimal('Price', 10, 2)->nullable();
            
            // Foreign Keys based on your Model
            $table->foreignId('Supplier_ID')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('Status_ID')->constrained('statuses')->onDelete('cascade');
            
            // Employee_ID is nullable because an asset might be in storage (unassigned)
            $table->foreignId('Employee_ID')->nullable()->constrained('users')->onDelete('set null');

            /** * Note on Issue-ID: 
             * In your model, you have 'Issue-ID' in fillable. 
             * Usually, an Asset has many Issues (one-to-many), 
             * but if you want to track a 'current' active issue here:
             */
            $table->unsignedBigInteger('Issue_ID')->nullable();

            // Laravel's built-in timestamps (created_at/updated_at) replace the 'Timestamp' field
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};