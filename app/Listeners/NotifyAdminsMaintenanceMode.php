<?php

namespace App\Listeners;

use Illuminate\Foundation\Events\MaintenanceModeEnabled;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class NotifyAdminsMaintenanceMode
{
    /**
     * Handle the event.
     *
     * @param  MaintenanceModeEnabled  $event
     * @return void
     */
    public function handle(MaintenanceModeEnabled $event)
    {
        $admins = User::where('role', 'admin')->get()->filter(fn($u) => $u->email);
        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new \App\Mail\GeneralOperationalMail(
                $admin,
                'System Maintenance Mode',
                'System Status',
                'The application has been officially placed into maintenance mode. All user-facing services are currently suspended for administrative work.',
                [
                    ['label' => 'Timestamp', 'value' => now()->format('M d, Y H:i')],
                    ['label' => 'Status', 'value' => 'Maintenance Active'],
                    ['label' => 'Impact', 'value' => 'Full System Lockdown']
                ],
                'Go to Dashboard',
                config('app.url') . '/dashboard/admin'
            ));
        }
    }
}
