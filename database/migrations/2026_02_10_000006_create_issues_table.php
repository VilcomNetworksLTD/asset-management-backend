<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('Employee_ID');
            $table->unsignedBigInteger('Asset_ID');
            $table->unsignedBigInteger('Ticket_ID')->nullable();
            $table->text('Issue_Description');
            $table->unsignedBigInteger('Status_ID');

            $table->timestamps(); // replaces 'Timestamp'

            // FOREIGN KEYS
            $table->foreign('Employee_ID')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('Asset_ID')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('Ticket_ID')->references('id')->on('tickets')->onDelete('set null');
            $table->foreign('Status_ID')->references('id')->on('statuses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
