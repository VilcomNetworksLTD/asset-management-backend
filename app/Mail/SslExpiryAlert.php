<?php

namespace App\Mail;

use App\Models\SslCertificate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SslExpiryAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public SslCertificate $certificate;
    public int $daysRemaining;

    public function __construct(SslCertificate $certificate, int $daysRemaining)
    {
        $this->certificate = $certificate;
        $this->daysRemaining = $daysRemaining;
    }

    public function build()
    {
        $subject = "SSL Expiry Alert: {$this->certificate->common_name} expires in {$this->daysRemaining} days";
        
        // You can create a view at resources/views/emails/ssl_expiry.blade.php
        // For now, we use raw text/html for simplicity
        return $this->subject($subject)
            ->html($this->generateHtmlContent());
    }

    private function generateHtmlContent(): string
    {
        return "
            <h1>SSL Certificate Expiry Warning</h1>
            <p>The certificate for <strong>{$this->certificate->common_name}</strong> is expiring soon.</p>
            <ul>
                <li><strong>Days Remaining:</strong> {$this->daysRemaining}</li>
                <li><strong>Expiry Date:</strong> {$this->certificate->expiry_date->format('Y-m-d')}</li>
                <li><strong>Installed On:</strong> {$this->certificate->installed_on} ({$this->certificate->installed_on_type})</li>
            </ul>
            <p>Please log in to the Asset Management System to acknowledge or renew this certificate.</p>
        ";
    }
}