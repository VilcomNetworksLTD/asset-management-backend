<?php

namespace App\Services;

use App\Models\SslCertificate;
use App\Models\SslCertificateChangeLog;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class SslCertificateService
{

    public function getAll()
    {
        return SslCertificate::with('assignedOwner:id,name,email')
            ->withCount('changeLogs')
            ->latest()
            ->get()
            ->map(fn($c) => $this->appendComputed($c));
    }

    public function paginated(array $filters)
    {
        $query = SslCertificate::with('assignedOwner:id,name,email')
            ->withCount('changeLogs');

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('common_name', 'like', "%{$s}%")
                    ->orWhere('installed_on', 'like', "%{$s}%")
                    ->orWhere('ca_vendor', 'like', "%{$s}%")
                    ->orWhere('ip_address', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['revocation_status'])) {
            $query->where('revocation_status', $filters['revocation_status']);
        }

        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 15)));

        return $query->latest()->paginate($perPage)->through(fn($c) => $this->appendComputed($c));
    }

    public function store(array $data): SslCertificate
    {
        // Auto-compute status from expiry
        $cert = new SslCertificate($data);
        $data['status'] = $cert->computeStatus();

        $cert = SslCertificate::create($data);

        // Change log: created
        $this->logChange($cert, 'created', [
            'new_expiry_date' => $cert->expiry_date,
            'new_serial_number' => $cert->serial_number,
            'notes' => 'Certificate added to AMS.',
        ]);

        $this->activityLog('Created', $cert);
        return $cert->load('assignedOwner:id,name,email');
    }

    public function update(int $id, array $data): SslCertificate
    {
        $cert = SslCertificate::findOrFail($id);
        $oldExpiry = $cert->expiry_date?->toDateString();
        $oldSerial = $cert->serial_number;

        // Recompute status if expiry changed
        if (!empty($data['expiry_date'])) {
            $tmp = new SslCertificate(['expiry_date' => $data['expiry_date']]);
            $data['status'] = $tmp->computeStatus();
        }

        $cert->update($data);

        // Detect renewal / replacement
        if (!empty($data['expiry_date']) && $data['expiry_date'] !== $oldExpiry) {
            $this->logChange($cert, 'renewed', [
                'old_expiry_date' => $oldExpiry,
                'new_expiry_date' => $cert->expiry_date,
                'old_serial_number' => $oldSerial,
                'new_serial_number' => $cert->serial_number,
                'notes' => 'Certificate renewed / replaced.',
            ]);
        } else {
            $this->logChange($cert, 'updated', ['notes' => 'Certificate details updated.']);
        }

        $this->activityLog('Updated', $cert);
        return $cert->fresh('assignedOwner:id,name,email');
    }

    public function delete(int $id): bool
    {
        $cert = SslCertificate::findOrFail($id);
        $this->activityLog('Deleted', $cert);
        return $cert->delete();
    }

    // ── Acknowledge 30-day alert ─────────────────────────────────────────────

    public function acknowledge30DayAlert(int $id): SslCertificate
    {
        $cert = SslCertificate::findOrFail($id);
        $cert->update([
            'alert_30_acknowledged_at' => now(),
            'alert_30_acknowledged_by' => Auth::id(),
        ]);
        $this->logChange($cert, 'updated', ['notes' => '30-day expiry alert acknowledged by IT Admin.']);
        return $cert->fresh();
    }
    public function scanCertificate(int $id): SslCertificate
    {
        $cert = SslCertificate::findOrFail($id);

        // Prefer IP address if available, as common_name might be a wildcard (e.g. *.domain.com)
        $host = $cert->ip_address ?: $cert->common_name;

        // If it's still a wildcard, we can't connect. Cry for help or try to strip the *.
        if (str_starts_with($host, '*.')) {
            $host = substr($host, 2);
        }

        $port = $cert->port ?: 443;

        try {
            $scanned = $this->fetchRemoteCert($host, $port);

            $updates = array_merge($scanned, [
                'last_scanned_at' => now(),
                'scan_source' => 'manual',
            ]);
            $updates['status'] = (new SslCertificate(['expiry_date' => $updates['expiry_date'] ?? $cert->expiry_date]))->computeStatus();

            $cert->update($updates);

            $this->logChange($cert, 'scanned', [
                'new_expiry_date' => $cert->fresh()->expiry_date,
                'new_serial_number' => $cert->fresh()->serial_number,
                'notes' => "Auto-scanned from {$host}:{$port}",
            ]);
        } catch (\Exception $e) {
            // Log the error but re-throw with a user-friendly message
            Log::error("SSL Scan failed for ID {$id}: " . $e . getMessage());
            throw new \Exception("Live scan failed: " . $e->getMessage());
        }

        return $cert->fresh('assignedOwner:id,name,email');
    }


    public function scanDomain(string $host, int $port = 443): array
    {
        return $this->fetchRemoteCert($host, $port);
    }



    public function checkRevocation(int $id): SslCertificate
    {
        $cert = SslCertificate::findOrFail($id);

        // Try a basic OCSP check via openssl (if available) or fallback to 'unknown'
        $revStatus = $this->ocspCheck($cert->common_name, $cert->port ?: 443);

        $cert->update([
            'revocation_status' => $revStatus,
            'revocation_checked_at' => now(),
        ]);

        $this->logChange($cert, 'scanned', [
            'notes' => "Revocation check: {$revStatus}",
        ]);

        return $cert->fresh();
    }

    public function processExpiryAlerts(): void
    {
        $certs = SslCertificate::whereNull('deleted_at')->get();

        foreach ($certs as $cert) {
            // Recompute and persist status each run
            $newStatus = $cert->computeStatus();
            if ($cert->status !== $newStatus) {
                $cert->update(['status' => $newStatus]);
            }

            $days = $cert->days_to_expiry;

            // 90-day alert
            if ($days <= 90 && $days > 60 && !$cert->alert_90_sent_at) {
                $this->sendAlert($cert, 90);
                $cert->update(['alert_90_sent_at' => now()]);
            }

            // 60-day alert
            if ($days <= 60 && $days > 30 && !$cert->alert_60_sent_at) {
                $this->sendAlert($cert, 60);
                $cert->update(['alert_60_sent_at' => now()]);
            }

            // 30-day alert → IT Admin
            if ($days <= 30 && $days > 14 && !$cert->alert_30_sent_at) {
                $this->sendAlert($cert, 30, 'it_admin');
                $cert->update(['alert_30_sent_at' => now()]);
            }

            // 14-day escalation → Network Admin (only if 30d alert was NOT acknowledged)
            if ($days <= 14 && $days > 7 && !$cert->alert_14_sent_at) {
                if (!$cert->alert_30_acknowledged_at) {
                    $this->sendAlert($cert, 14, 'network_admin');
                }
                $cert->update(['alert_14_sent_at' => now()]);
            }

            // 7-day critical alert
            if ($days <= 7 && $days >= 0 && !$cert->alert_7_sent_at) {
                $this->sendAlert($cert, 7, 'both');
                $cert->update(['alert_7_sent_at' => now()]);
            }
        }
    }



    public function getChangeLogs(int $id)
    {
        return SslCertificateChangeLog::with('changedBy:id,name,email')
            ->where('ssl_certificate_id', $id)
            ->latest()
            ->get();
    }



    private function fetchRemoteCert(string $host, int $port): array
    {
        $ctx = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        // Use a shorter timeout (5s) for the initial connection
        $socket = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            5,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        if (!$socket) {
            // THROW the error so the controller knows it failed
            throw new \Exception("Connection failed: {$errstr} ({$errno})");
        }

        $params = stream_context_get_params($socket);
        $certResource = $params['options']['ssl']['peer_certificate'] ?? null;
        fclose($socket);

        if (!$certResource) {
            throw new \Exception("No SSL certificate found on {$host}:{$port}");
        }

        $parsed = openssl_x509_parse($certResource);

        // Issuer / CA
        $caVendor = $parsed['issuer']['O'] ?? $parsed['issuer']['CN'] ?? 'Unknown';

        return [
            'common_name' => $parsed['subject']['CN'] ?? $host,
            'serial_number' => $parsed['serialNumberHex'] ?? strtoupper(dechex($parsed['serialNumber'] ?? 0)),
            'expiry_date' => date('Y-m-d', $parsed['validTo_time_t']),
            'ca_vendor' => $caVendor,
        ];
    }
    public function discoverRange(string $rangePrefix, int $port = 443): void
    {
        // Warning: This should be dispatched to a Background Job/Queue
        for ($i = 1; $i <= 254; $i++) {
            $ip = $rangePrefix . $i;
            try {
                $data = $this->fetchRemoteCert($ip, $port);
                if (!empty($data)) {
                    $cert = SslCertificate::updateOrCreate(
                        ['common_name' => $data['common_name'], 'port' => $port],
                        array_merge($data, [
                            'ip_address' => $ip,
                            'scan_source' => 'auto_discovery',
                            'last_scanned_at' => now()
                        ])
                    );
                    $this->activityLog('Discovered', $cert);
                }
            } catch (\Exception $e) {
                // Silence errors for discovery range to continue to next IP
                continue;
            }
        }
    }
    private function ocspCheck(string $host, int $port): string
    {

        if (PHP_OS_FAMILY === 'Windows') {

            return 'unknown';
        }

        $cmd = "echo Q | openssl s_client -connect {$host}:{$port} -status 2>/dev/null | grep -i 'OCSP Response Status'";
        $output = shell_exec($cmd);

        if ($output && stripos($output, 'successful') !== false) {
            return stripos($output, 'revoked') !== false ? 'revoked' : 'valid';
        }

        return 'unknown';
    }

    private function sendAlert(SslCertificate $cert, int $days, string $target = 'it_admin'): void
    {
        // Resolve recipients
        $roles = match ($target) {
            'network_admin' => ['network_admin'],
            'both' => ['admin', 'network_admin'],
            default => ['admin'],
        };

        $recipients = User::whereIn('role', $roles)->get();

        foreach ($recipients as $user) {
            Mail::to($user->email)->queue(new SslExpiryAlert($cert, $days));
        }

        // Also persist as an activity log so admins can see it in the AMS
        ActivityLog::create([
            'Employee_ID' => null,
            'user_name' => 'System',
            'action' => 'Alert',
            'target_type' => 'SslCertificate',
            'target_name' => $cert->common_name,
            'details' => "{$days}-day expiry alert dispatched. Days remaining: {$cert->days_to_expiry}.",
        ]);
    }

    private function logChange(SslCertificate $cert, string $action, array $extra = []): void
    {
        SslCertificateChangeLog::create(array_merge([
            'ssl_certificate_id' => $cert->id,
            'action' => $action,
            'changed_by' => Auth::id(),
        ], $extra));
    }

    private function activityLog(string $action, SslCertificate $cert): void
    {
        ActivityLog::create([
            'Employee_ID' => Auth::id(),
            'user_name' => Auth::user()?->name ?? 'System',
            'action' => $action,
            'target_type' => 'SslCertificate',
            'target_name' => $cert->common_name,
            'details' => "Port: {$cert->port} | Installed on: {$cert->installed_on} | Expires: {$cert->expiry_date}",
        ]);
    }

    private function appendComputed(SslCertificate $cert): SslCertificate
    {
        $cert->setAttribute('days_to_expiry', $cert->days_to_expiry);
        return $cert;
    }
}
