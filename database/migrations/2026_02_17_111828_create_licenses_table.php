<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLicensesTable extends Migration
{
    public function up()
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id(); // Consistent with other tables
            $table->string('name');
            $table->string('product_key')->nullable();
            $table->string('manufacturer')->nullable();
            $table->integer('total_seats')->default(1);
            $table->integer('remaining_seats')->default(1);
            $table->decimal('price', 10, 2)->nullable(); // Matches your price format
            $table->timestamps(); // Required for recent activity tracking
        });
    }

    public function down()
    {
        Schema::dropIfExists('licenses');
    }
}