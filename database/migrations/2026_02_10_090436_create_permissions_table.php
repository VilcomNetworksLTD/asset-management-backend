<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('Employee_ID');
            $table->string('Role');
            $table->string('Permission_Level');
            $table->string('Module');

            $table->timestamps(); // Laravel default timestamps

            // FOREIGN KEY
            $table->foreign('Employee_ID')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
