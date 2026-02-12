<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('staff')->after('password');
            }

            if (!Schema::hasColumn('users', 'otp_code')) {
                $table->string('otp_code', 6)->nullable()->after('role');
            }

            if (!Schema::hasColumn('users', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            }

            if (!Schema::hasColumn('users', 'reset_otp')) {
                $table->string('reset_otp', 6)->nullable()->after('otp_expires_at');
            }

            if (!Schema::hasColumn('users', 'reset_otp_expires_at')) {
                $table->timestamp('reset_otp_expires_at')->nullable()->after('reset_otp');
            }

            if (!Schema::hasColumn('users', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('reset_otp_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 
                'otp_code', 
                'otp_expires_at', 
                'reset_otp', 
                'reset_otp_expires_at', 
                'is_verified'
            ]);
        });
    }
};
