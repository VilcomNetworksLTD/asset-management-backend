<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('assets', function (Blueprint $table) {
        $table->date('Purchase_Date')->nullable()->after('location');
        $table->date('warranty_expiry')->nullable(); // For the date
        $table->string('warranty_image_path')->nullable(); // For the image file path
    });
}

public function down()
{
    Schema::table('assets', function (Blueprint $table) {
        $table->dropColumn(['Purchase_Date', 'warranty_expiry', 'warranty_image_path']);
    });
}
};
