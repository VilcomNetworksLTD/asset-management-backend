<?php

namespace App\Console\Commands;

use App\Services\SslCertificateService;
use App\Models\SslCertificate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SslCertificateDailyScan extends Command
{
    protected $signature   = 'ssl:daily-scan {--alerts-only : Skip live scans, only process expiry alerts}';
    protected $description = 'Daily: re-scan all certificate hosts, refresh revocation status, fire multi-stage alerts.';

    public function __construct(private SslCertificateService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('[SSL] Starting daily certificate job…');

        if (!$this->option('alerts-only')) {
            $this->runLiveScans();
            $this->runRevocationChecks();
        }

        $this->info('[SSL] Processing multi-stage expiry alerts…');
        $this->service->processExpiryAlerts();
        $this->info('[SSL] Alert processing complete.');

        return self::SUCCESS;
    }

    // ── Live re-scan every certificate ──────────────────────────────────────

    private function runLiveScans(): void
    {
        $certs = SslCertificate::whereNull('deleted_at')->get();
        $this->info("[SSL] Scanning {$certs->count()} certificate(s)…");

        $bar = $this->output->createProgressBar($certs->count());
        $bar->start();

        foreach ($certs as $cert) {
            try {
                $this->service->scanCertificate($cert->id);
            } catch (\Throwable $e) {
                Log::warning("[SSL] Scan failed for {$cert->common_name}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('[SSL] Live scans complete.');
    }

    // ── Revocation checks ────────────────────────────────────────────────────

    private function runRevocationChecks(): void
    {
        // Only re-check certs that have not been checked in the last 6 hours
        $certs = SslCertificate::whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('revocation_checked_at')
                  ->orWhere('revocation_checked_at', '<', now()->subHours(6));
            })
            ->get();

        $this->info("[SSL] Checking revocation status for {$certs->count()} certificate(s)…");

        foreach ($certs as $cert) {
            try {
                $this->service->checkRevocation($cert->id);
            } catch (\Throwable $e) {
                Log::warning("[SSL] Revocation check failed for {$cert->common_name}: " . $e->getMessage());
            }
        }

        $this->info('[SSL] Revocation checks complete.');
    }
}
