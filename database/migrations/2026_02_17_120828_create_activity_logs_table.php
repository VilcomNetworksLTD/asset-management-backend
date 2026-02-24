<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');   // Admin or Staff name
            $table->string('action');      // Created, Updated, Deleted, Checked Out
            $table->string('target_type'); // Asset, License, Component, etc.
            $table->string('target_name'); // e.g., "MacBook Pro #001"
            $table->text('details')->nullable(); // Changes made
            $table->timestamps(); // The "When"
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
}