<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SslCertificate;

class CheckSslExpiry extends Command
{
    protected $signature = 'ssl:check-expiry';
    protected $description = 'Check SSL certificates expiry and alert';

    public function handle()
    {
        SslCertificate::all()->each(function ($cert) {
            if ($cert->days_to_expiry <= 30 && !$cert->alert_30_sent_at) {
                $cert->update(['alert_30_sent_at' => now()]);
            }
        });
    }
}