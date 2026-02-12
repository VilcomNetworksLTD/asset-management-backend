<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('Asset_ID');
            $table->unsignedBigInteger('Employee_ID');
            $table->text('Comments')->nullable();

            $table->timestamps(); // replaces your custom 'Timestamp'

            // FOREIGN KEYS
            $table->foreign('Asset_ID')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('Employee_ID')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
