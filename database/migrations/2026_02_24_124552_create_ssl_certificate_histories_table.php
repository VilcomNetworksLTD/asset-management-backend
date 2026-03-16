<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ssl_certificate_histories', function (Blueprint $table) {
            $table->id();
            
            // Foreign key linking to your main certificates table
            // Ensure the table 'ssl_certificates' exists before running this
            $table->foreignId('ssl_certificate_id')
            ->constrained('ssl_certificates')
            ->onDelete('cascade');
            
            // Audit details
            $table->enum('action', ['created', 'renewed', 'replaced', 'revoked', 'expired', 'modified']);
            
            // Tracks which admin performed the action
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            
            // Expiry tracking (useful for comparing old vs new during renewal)
            $table->date('old_expiry_date')->nullable();
            $table->date('new_expiry_date')->nullable();
            
            // Serial number tracking
            $table->string('old_serial_number')->nullable();
            $table->string('new_serial_number')->nullable();
            
            // Validation for security audits
            $table->boolean('was_properly_revoked')->default(false);
            
            $table->text('notes')->nullable();
            
            // Timestamps handle created_at and updated_at
            $table->timestamps();
            
            // SoftDeletes allows for recovery of accidental history deletions
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ssl_certificate_histories');
    }
};