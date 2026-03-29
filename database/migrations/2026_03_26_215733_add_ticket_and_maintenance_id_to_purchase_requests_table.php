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
            $table->foreignId('ticket_id')->nullable()->after('user_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('maintenance_id')->nullable()->after('ticket_id')->constrained('maintenance')->onDelete('cascade');
            $table->text('notes')->nullable()->after('rejection_reason');
            $table->timestamp('approved_at')->nullable()->after('notes');
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
