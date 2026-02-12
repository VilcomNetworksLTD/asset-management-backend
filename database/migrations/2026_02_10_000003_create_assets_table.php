<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id(); // primary key

            $table->string('Asset_Name');
            $table->string('Asset_Category');
            $table->string('Serial_No')->unique()->nullable();
            $table->unsignedBigInteger('Supplier_ID');
            $table->unsignedBigInteger('Employee_ID');
            $table->unsignedBigInteger('Status_ID');
            $table->text('Warranty_Details')->nullable();
            $table->text('License_Info')->nullable();
            $table->decimal('Price', 15, 2)->nullable();
            $table->unsignedBigInteger('Issue_ID')->nullable(); // renamed from Issue-ID

            $table->timestamps(); // Laravel default created_at and updated_at

            // FOREIGN KEYS
            $table->foreign('Employee_ID')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('Supplier_ID')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('Status_ID')->references('id')->on('statuses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
