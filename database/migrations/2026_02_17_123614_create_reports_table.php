<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');          // e.g., "Monthly Asset Audit - Feb 2026"
            $table->string('type');           // e.g., "PDF", "CSV", "XLSX"
            $table->string('category');       // e.g., "Inventory", "Expense", "Activity"
            $table->string('file_path')->nullable(); // Location of the stored file
            $table->string('generated_by');   // Name of the admin who created it
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
}