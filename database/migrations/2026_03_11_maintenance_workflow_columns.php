<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance', function (Blueprint $table) {
            if (!Schema::hasColumn('maintenance', 'Workflow_Status')) {
                $table->string('Workflow_Status')->nullable()->after('Status_ID');
            }
            if (!Schema::hasColumn('maintenance', 'Actioned_By')) {
                $table->unsignedBigInteger('Actioned_By')->nullable()->after('Workflow_Status');
            }
            if (!Schema::hasColumn('maintenance', 'Actioned_At')) {
                $table->dateTime('Actioned_At')->nullable()->after('Actioned_By');
            }
        });
    }

    public function down(): void
    {
        Schema::table('maintenance', function (Blueprint $table) {
            $table->dropColumn(['Workflow_Status', 'Actioned_By', 'Actioned_At']);
        });
    }
};