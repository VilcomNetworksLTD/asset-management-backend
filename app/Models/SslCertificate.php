<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SslCertificate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'common_name', 'sans', 'serial_number', 'fingerprint',
        'issued_date', 'expiry_date', 'ca_vendor', 'secret_key_storage',
        'port', 'installed_on', 'installed_on_type', 'ip_address',
        'assigned_owner_id', 'status', 'revocation_status',
        'revocation_checked_at', 'last_scanned_at', 'scan_source',
        'alert_90_sent_at', 'alert_60_sent_at', 'alert_30_sent_at',
        'alert_30_acknowledged_at', 'alert_30_acknowledged_by',
        'alert_14_sent_at', 'alert_7_sent_at', 'notes'
    ];

    protected $casts = [
        'sans' => 'array',
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'revocation_checked_at' => 'datetime',
        'last_scanned_at' => 'datetime',
        'alert_90_sent_at' => 'datetime',
        'alert_60_sent_at' => 'datetime',
        'alert_30_sent_at' => 'datetime',
        'alert_30_acknowledged_at' => 'datetime',
        'alert_14_sent_at' => 'datetime',
        'alert_7_sent_at' => 'datetime',
    ];

    public function assignedOwner()
    {
        return $this->belongsTo(User::class, 'assigned_owner_id');
    }

    public function changeLogs()
    {
        return $this->hasMany(SslCertificateChangeLog::class);
    }

    // Accessor for days_to_expiry
    public function getDaysToExpiryAttribute()
    {
        if (!$this->expiry_date) {
            return 0;
        }
        // false = return float (days), we cast to int. Positive if future, negative if past.
        return (int) now()->diffInDays($this->expiry_date, false);
    }

    // Logic for Red/Yellow/Green status
    public function computeStatus(): string
    {
        $days = $this->days_to_expiry;

        if ($days <= 7) return 'red';     // Critical / Expired
        if ($days <= 30) return 'yellow'; // Warning
        return 'green';                   // OK
    }
}