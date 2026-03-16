<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SslCertificateService;

class SslMonitorCommand extends Command
{
    protected $signature = 'ssl:monitor';
    protected $description = 'Check SSL certificates for expiry and send alerts';

    protected SslCertificateService $service;

    public function __construct(SslCertificateService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        $this->info('Starting SSL Certificate monitoring...');
        
        $this->service->processExpiryAlerts();
        
        $this->info('SSL monitoring complete.');
    }
}