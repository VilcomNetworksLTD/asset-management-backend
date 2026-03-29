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
        Schema::create('consumable_color_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_id')->constrained()->onDelete('cascade');
            $table->string('color');
            $table->integer('in_stock')->default(0);
            $table->integer('min_amt')->default(0);
            $table->timestamps();

            $table->unique(['consumable_id', 'color']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumable_color_stocks');
    }
};
