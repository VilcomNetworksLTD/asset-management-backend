<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('transfers', 'Sender_ID')) {
                $table->unsignedBigInteger('Sender_ID')->nullable()->after('Employee_ID');
                $table->foreign('Sender_ID')->references('id')->on('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('transfers', 'Receiver_ID')) {
                $table->unsignedBigInteger('Receiver_ID')->nullable()->after('Sender_ID');
                $table->foreign('Receiver_ID')->references('id')->on('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('transfers', 'Type')) {
                $table->string('Type')->nullable()->after('Transfer_Date');
            }

            if (!Schema::hasColumn('transfers', 'Workflow_Status')) {
                $table->string('Workflow_Status')->nullable()->after('Type');
            }

            if (!Schema::hasColumn('transfers', 'Sender_Condition')) {
                $table->string('Sender_Condition')->nullable()->after('Workflow_Status');
            }

            if (!Schema::hasColumn('transfers', 'Admin_Condition')) {
                $table->string('Admin_Condition')->nullable()->after('Sender_Condition');
            }

            if (!Schema::hasColumn('transfers', 'Included_Items')) {
                $table->json('Included_Items')->nullable()->after('Admin_Condition');
            }

            if (!Schema::hasColumn('transfers', 'Missing_Items')) {
                $table->json('Missing_Items')->nullable()->after('Included_Items');
            }

            if (!Schema::hasColumn('transfers', 'Notes')) {
                $table->text('Notes')->nullable()->after('Missing_Items');
            }

            if (!Schema::hasColumn('transfers', 'Actioned_By')) {
                $table->unsignedBigInteger('Actioned_By')->nullable()->after('Notes');
                $table->foreign('Actioned_By')->references('id')->on('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('transfers', 'Actioned_At')) {
                $table->dateTime('Actioned_At')->nullable()->after('Actioned_By');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            foreach (['Sender_ID', 'Receiver_ID', 'Actioned_By'] as $fkColumn) {
                try {
                    $table->dropForeign([$fkColumn]);
                } catch (\Throwable $e) {
                    // no-op
                }
            }

            foreach ([
                'Sender_ID',
                'Receiver_ID',
                'Type',
                'Workflow_Status',
                'Sender_Condition',
                'Admin_Condition',
                'Included_Items',
                'Missing_Items',
                'Notes',
                'Actioned_By',
                'Actioned_At',
            ] as $column) {
                if (Schema::hasColumn('transfers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
