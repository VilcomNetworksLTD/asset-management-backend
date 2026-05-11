<?php

namespace App\Services;



use App\Models\{Asset, Transfer, User, License, Accessory, Consumable, SslCertificate, Ticket, Maintenance};
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getUserAssets($userId)
    {
        return Transfer::with(['asset', 'status'])
            ->where('Employee_ID', $userId)
            ->latest()
            ->get();
    }

    public function getStats()
    {
        $assets = Asset::count();
        $licenses = License::count();
        $accessories = Accessory::count();
        $users = User::count();
        $ssl_certificates = SslCertificate::count();
        $tickets = Ticket::count();

        return [
            // Legacy keys
            'total_assets'      => $assets,
            'total_licenses'    => $licenses,
            'total_accessories' => $accessories,
            'total_users'       => $users,
            'total_ssl_certificates' => $ssl_certificates,

            // Frontend-friendly keys
            'assets'            => $assets,
            'licenses'          => $licenses,
            'accessories'       => $accessories,
            'tickets'           => $tickets,
            'out_for_repair'    => Asset::whereIn('Status_ID', \App\Models\Status::whereIn('Status_Name', ['Out for Repair', 'Maintenance', 'Under Repair'])->pluck('id'))->count(),
            'pending_maintenance' => Maintenance::whereNull('Completion_Date')->count(),
            'ssl_certificates'  => $ssl_certificates,
            'people'            => $users,

            'status_distribution' => [
                'ready_to_deploy' => Asset::whereNotIn('id', function($query) {
                        $query->select('Asset_ID')->from('maintenance')->where('Workflow_Status', '!=', 'Completed');
                    })->whereNotIn('Status_ID', function($query) {
                        $query->select('id')->from('statuses')
                            ->whereRaw('LOWER(Status_Name) IN ("deployed", "assigned", "in use", "checked out", "archived", "archive", "broken", "lost", "stolen")');
                    })->where(function($q) {
                        $q->whereNull('Employee_ID')->orWhere('Employee_ID', 0);
                    })->count(),
                'deployed' => Asset::where(function($q) {
                        $q->whereNotNull('Employee_ID')->where('Employee_ID', '>', 0);
                    })->whereNotIn('Status_ID', function($query) {
                        $query->select('id')->from('statuses')
                            ->whereRaw('LOWER(Status_Name) IN ("archived", "archive", "broken", "lost", "stolen")');
                    })->whereNotIn('id', function($query) {
                        $query->select('Asset_ID')->from('maintenance')->where('Workflow_Status', '!=', 'Completed');
                    })->count(),
                'archived' => Asset::whereIn('Status_ID', function($query) {
                        $query->select('id')->from('statuses')
                            ->whereRaw('LOWER(Status_Name) IN ("archived", "archive", "broken", "lost", "stolen")');
                    })->whereNotIn('id', function($query) {
                        $query->select('Asset_ID')->from('maintenance')->where('Workflow_Status', '!=', 'Completed');
                    })->count(),
                'out_for_repair' => Asset::whereIn('id', function($query) {
                        $query->select('Asset_ID')->from('maintenance')
                            ->where('Workflow_Status', '!=', 'Completed');
                    })->count(),
            ],
            'recent_activity' => \App\Models\ActivityLog::latest()->take(10)->get()->map(fn($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'user_name' => $log->user_name,
                'target_name' => $log->target_name,
                'target_type' => $log->target_type,
                'details' => $log->details,
                'created_at' => $log->created_at,
                'time' => $log->created_at->diffForHumans(),
            ])
        ];
    }

    public function getTransfersData()
    {
        return Transfer::with(['asset', 'user', 'status'])
            ->latest()
            ->paginate(10);
    }
}