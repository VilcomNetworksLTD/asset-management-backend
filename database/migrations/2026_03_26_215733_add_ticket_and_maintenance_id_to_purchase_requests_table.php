<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_requests', 'ticket_id')) {
                $table->foreignId('ticket_id')->nullable()->after('user_id')->constrained('tickets')->onDelete('cascade');
            }
            if (!Schema::hasColumn('purchase_requests', 'maintenance_id')) {
                $table->foreignId('maintenance_id')->nullable()->after('ticket_id')->constrained('maintenances')->onDelete('cascade');
            }
            if (!Schema::hasColumn('purchase_requests', 'notes')) {
                $table->text('notes')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('purchase_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropForeign(['maintenance_id']);
            $table->dropColumn(['ticket_id', 'maintenance_id', 'notes', 'approved_at']);
        });
    }
};
