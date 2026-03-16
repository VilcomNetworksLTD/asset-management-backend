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
        Schema::create('asset_consumables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete(); // The Printer
            $table->foreignId('consumable_id')->constrained('consumables')->cascadeOnDelete(); // The Ink Box
            
            // The specific color (Cyan, Magenta, Yellow, Black, etc.)
            $table->string('color'); 
            
            // The Lifecycle Timestamps
            $table->timestamp('installed_at')->useCurrent(); // When it started being used
            $table->timestamp('depleted_at')->nullable();    // When it finished (Null means it is currently active)
            
            $table->foreignId('installed_by')->nullable()->constrained('users'); // Which admin installed it
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_consumables');
    }
};