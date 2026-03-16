SslCertificate::all()->each(function ($cert) {
    if ($cert->days_to_expiry <= 30 && !$cert->alert_30_sent_at) {
        // Send email / notification
        $cert->update(['alert_30_sent_at' => now()]);
    }
});