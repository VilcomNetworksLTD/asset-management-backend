<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('Asset_ID');
            $table->unsignedBigInteger('Employee_ID');
            $table->unsignedBigInteger('Sender_ID')->nullable();
            $table->unsignedBigInteger('Status_ID');

            $table->dateTime('Request_Date')->nullable();
            $table->string('Workflow_Status')->nullable();
            $table->string('Sender_Condition')->nullable();
            $table->string('Admin_Condition')->nullable();
            $table->json('Missing_Items')->nullable();
            $table->text('Notes')->nullable();

            $table->unsignedBigInteger('Actioned_By')->nullable();
            $table->dateTime('Actioned_At')->nullable();

            $table->timestamps();

            $table->foreign('Asset_ID')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('Employee_ID')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('Sender_ID')->references('id')->on('users')->nullOnDelete();
            $table->foreign('Status_ID')->references('id')->on('statuses')->onDelete('cascade');
            $table->foreign('Actioned_By')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
