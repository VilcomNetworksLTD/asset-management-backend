<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('Employee_ID');
            $table->unsignedBigInteger('Issue_ID')->nullable();
            $table->unsignedBigInteger('Status_ID');
            $table->string('Priority')->default('Medium');
            $table->text('Description');
            $table->text('Communication_log')->nullable();

            $table->timestamps(); // replaces your custom 'Timestamp'

            // FOREIGN KEYS
            $table->foreign('Employee_ID')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('Issue_ID')->references('id')->on('issues')->onDelete('set null');
            $table->foreign('Status_ID')->references('id')->on('statuses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
