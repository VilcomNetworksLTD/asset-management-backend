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
        Schema::create('accessory_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('accessory_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('component_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('component_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('consumable_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('consumable_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('license_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('license_id')->constrained()->onDelete('cascade');
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accessory_user');
        Schema::dropIfExists('component_user');
        Schema::dropIfExists('consumable_user');
        Schema::dropIfExists('license_user');
    }
};
