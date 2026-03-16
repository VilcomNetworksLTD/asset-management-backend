<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // register model observers
        \App\Models\ActivityLog::observe(\App\Observers\ActivityLogObserver::class);

        // notify admins when the framework enters maintenance mode
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Foundation\Events\MaintenanceModeEnabled::class,
            \App\Listeners\NotifyAdminsMaintenanceMode::class
        );
    }
}
