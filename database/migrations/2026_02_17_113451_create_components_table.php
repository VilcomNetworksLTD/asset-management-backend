<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComponentsTable extends Migration
{
    public function up()
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->string('category'); 
            $table->string('serial_no')->nullable();
            $table->integer('total_qty')->default(0);
            $table->integer('remaining_qty')->default(0);
            $table->foreignId('asset_id')->nullable()->constrained('assets');
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('components');
    }
}