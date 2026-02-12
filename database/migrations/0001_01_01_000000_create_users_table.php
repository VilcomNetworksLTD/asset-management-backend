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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // This is the 'id' referenced as Employee_ID elsewhere
            
            // Basic Info
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('staff'); // e.g., 'admin', 'technician', 'staff'

            // OTP & Verification Logic (From your Model)
            $table->boolean('is_verified')->default(false);
            $table->string('otp_code')->nullable();
            $table->dateTime('otp_expires_at')->nullable();
            $table->string('reset_otp')->nullable();
            $table->dateTime('reset_otp_expires_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};