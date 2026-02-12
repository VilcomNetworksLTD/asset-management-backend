<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('Asset_ID');
            $table->unsignedBigInteger('Ticket_ID')->nullable();
            $table->dateTime('Request_Date');
            $table->dateTime('Completion_Date')->nullable();
            $table->string('Maintenance_Type');
            $table->text('Description')->nullable();
            $table->decimal('Cost', 15, 2)->nullable();
            $table->unsignedBigInteger('Status_ID');
            $table->dateTime('Maintenance_Date')->nullable();

            $table->timestamps(); // Laravel default timestamps

            // FOREIGN KEYS
            $table->foreign('Asset_ID')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('Ticket_ID')->references('id')->on('tickets')->onDelete('set null');
            $table->foreign('Status_ID')->references('id')->on('statuses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance');
    }
};
