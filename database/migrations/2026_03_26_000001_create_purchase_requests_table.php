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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->onDelete('cascade');
            $table->foreignId('maintenance_id')->nullable()->constrained('maintenance')->onDelete('cascade');
            $table->string('type'); // 'asset_request', 'maintenance_part'
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected', 'purchased'
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('management_id')->nullable();
            $table->foreign('management_id')->references('id')->on('users');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
