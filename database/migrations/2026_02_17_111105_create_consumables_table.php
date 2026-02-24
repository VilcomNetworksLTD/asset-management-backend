<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsumablesTable extends Migration
{
    public function up()
    {
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->string('item_name'); // Matches "ITEM NAME" in UI
            $table->string('category');  // Matches "CATEGORY" in UI
            $table->integer('in_stock')->default(0); // Matches "IN STOCK" in UI
            $table->decimal('price', 10, 2)->nullable(); // Matches "PRICE" in UI
            $table->integer('min_amt')->default(0); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('consumables');
    }
}