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
        // Matches $table = 'permissions' in your model
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            
            // Link to the User
            $table->foreignId('Employee_ID')->constrained('users')->onDelete('cascade');
            
            // Permission Details
            $table->string('Role');             // e.g., 'Admin', 'Technician', 'Staff'
            $table->string('Permission_Level'); // e.g., 'Read', 'Write', 'Full-Access'
            $table->string('Module');           // e.g., 'Assets', 'Tickets', 'Users'
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};