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
            $table->id();
            $table->string('asset_name');
            $table->string('asset_category');
            $table->string('serial_no')->unique();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('status_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2)->nullable();
            $table->text('warranty_details')->nullable();
            $table->text('license_info')->nullable();
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
