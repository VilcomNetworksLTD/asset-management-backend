<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SslCertificateChangeLog extends Model
{
    protected $fillable = [
        'ssl_certificate_id', 'action', 'changed_by',
        'old_expiry_date', 'new_expiry_date',
        'old_serial_number', 'new_serial_number',
        'was_properly_revoked', 'notes'
    ];

    protected $casts = [
        'old_expiry_date' => 'date',
        'new_expiry_date' => 'date',
    ];

    public function certificate()
    {
        return $this->belongsTo(SslCertificate::class, 'ssl_certificate_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}