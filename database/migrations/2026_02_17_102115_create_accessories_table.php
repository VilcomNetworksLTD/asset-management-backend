<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Give the class an explicit name instead of an anonymous one
class CreateAccessoriesTable extends Migration
{
    public function up()
    {
        Schema::create('accessories', function (Blueprint $table) {
            $table->id(); 
            $table->string('name');
            $table->string('category');       
            $table->string('model_number')->nullable();
            $table->integer('total_qty')->default(0);
            $table->integer('remaining_qty')->default(0);
            $table->decimal('price', 10, 2)->nullable(); 
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('accessories');
    }
}