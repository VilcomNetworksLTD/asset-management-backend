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
        \Illuminate\Support\Facades\Log::info('Incoming request: ' . \Illuminate\Support\Facades\Request::url());
        // register model observers
        \App\Models\ActivityLog::observe(\App\Observers\ActivityLogObserver::class);
        \App\Models\Asset::observe(\App\Observers\AssetObserver::class);
        \App\Models\Maintenance::observe(\App\Observers\MaintenanceObserver::class);
        \App\Models\Ticket::observe(\App\Observers\TicketObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\Supplier::observe(\App\Observers\SupplierObserver::class);
        \App\Models\Issue::observe(\App\Observers\IssueObserver::class);

        // notify admins when the framework enters maintenance mode
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Foundation\Events\MaintenanceModeEnabled::class,
            \App\Listeners\NotifyAdminsMaintenanceMode::class
        );
    }
}
