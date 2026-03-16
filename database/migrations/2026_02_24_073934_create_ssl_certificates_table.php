<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Main SSL Certificates Table ──────────────────────────────────────
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();

            // Core certificate identity
            $table->string('common_name');                         
            $table->json('sans')->nullable();                       
            $table->string('serial_number')->nullable();            
            $table->string('fingerprint')->nullable();              

            // Validity window
            $table->date('issued_date')->nullable();
            $table->date('expiry_date');                           

            // CA & key info
            $table->string('ca_vendor')->nullable();                
            $table->string('secret_key_storage')->nullable();      
            $table->unsignedSmallInteger('port')->default(443);    

            // Installation target
            $table->string('installed_on')->nullable();            
            $table->enum('installed_on_type', [
                'load_balancer', 'web_server', 'application', 'cdn', 'other'
            ])->default('web_server');
            $table->string('ip_address')->nullable();              

            // Ownership
            $table->foreignId('assigned_owner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Status & revocation (computed / cached)
            $table->enum('status', ['green', 'yellow', 'red'])->default('green');
            $table->enum('revocation_status', ['valid', 'revoked', 'unknown'])->default('unknown');
            $table->timestamp('revocation_checked_at')->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->string('scan_source')->default('manual');      

            // Multi-stage alert tracking
            $table->timestamp('alert_90_sent_at')->nullable();
            $table->timestamp('alert_60_sent_at')->nullable();
            $table->timestamp('alert_30_sent_at')->nullable();
            $table->timestamp('alert_30_acknowledged_at')->nullable();
            $table->foreignId('alert_30_acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('alert_14_sent_at')->nullable();      // Escalation if 30-day not acknowledged
            $table->timestamp('alert_7_sent_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Change Log Table ─────────────────────────────────────────────────
        Schema::create('ssl_certificate_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ssl_certificate_id')->constrained('ssl_certificates')->cascadeOnDelete();
            $table->enum('action', ['created', 'renewed', 'replaced', 'revoked', 'updated', 'scanned']);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();

            // Old vs new snapshot (for replaced / renewed)
            $table->date('old_expiry_date')->nullable();
            $table->date('new_expiry_date')->nullable();
            $table->string('old_serial_number')->nullable();
            $table->string('new_serial_number')->nullable();
            $table->boolean('was_properly_revoked')->nullable();    // Was the old cert revoked via CA?

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certificate_change_logs');
        Schema::dropIfExists('ssl_certificates');
    }
};
