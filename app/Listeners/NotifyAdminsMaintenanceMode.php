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
        $subject = 'Application Entered Maintenance Mode';
        $details = "The application has been put into maintenance mode at " . now() . ".\n" .
                   "Please plan accordingly and inform users as necessary.";

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new \App\Mail\SimpleNotification($subject, $details));
        }
    }
}
