<?php

namespace App\Services;



use App\Models\{Asset, Transfer, User, License, Accessory, Consumable, SslCertificate, Ticket};
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
        $consumables = Consumable::count();

        $users = User::count();
        $ssl_certificates = SslCertificate::count();
        $tickets = Ticket::count();

        return [
            // Legacy keys
            'total_assets' => $assets,
            'total_licenses' => $licenses,
            'total_accessories' => $accessories,

            'total_users' => $users,
            'total_ssl_certs' => $ssl_certificates,

            // Updated labels
            'assets' => $assets,
            'licenses' => $licenses,
            'accessories' => $accessories,

            'tickets' => $tickets,
            'ssl_certificates' => $ssl_certificates,
            'people' => $users,

            'status_distribution' => [
                'ready_to_deploy' => Asset::where(function ($q) {
                    $q->whereNull('Employee_ID')->orWhere('Employee_ID', 0);
                })->whereIn('Status_ID', function ($query) {
                    $query->select('id')->from('statuses')
                        ->whereIn('Status_Name', ['Ready to Deploy', 'Available', 'Ready', 'In Stock', 'Active', 'New']);
                })->count(),
                'deployed' => Asset::where(function ($q) {
                    $q->whereNotNull('Employee_ID')->where('Employee_ID', '>', 0);
                })->orWhereIn('Status_ID', function ($query) {
                    $query->select('id')->from('statuses')
                        ->whereIn('Status_Name', ['Deployed', 'Assigned', 'In Use', 'Checked Out']);
                })->count(),
                'archived' => Asset::whereIn('Status_ID', function ($query) {
                    $query->select('id')->from('statuses')
                        ->whereIn('Status_Name', ['Archived', 'Archive', 'Broken', 'Lost', 'Stolen']);
                })->count(),
            ],
            // Requirement 8: Inventory Health Logic
            'inventory_health' => [
                'operational' => Asset::whereIn('Status_ID', function ($q) {
                    $q->select('id')->from('statuses')->whereIn('Status_Name', ['Deployed', 'Ready to Deploy', 'Available']);
                })->count(),
                'non_operational' => Asset::whereIn('Status_ID', function ($q) {
                    $q->select('id')->from('statuses')->whereIn('Status_Name', ['Broken', 'Out for Repair', 'Lost', 'Stolen']);
                })->count(),
            ],
        ];
    }

    /**
     * Requirement 8: Recent Activity for Dashboard
     */
    public function getRecentActivity()
    {
        return \App\Models\ActivityLog::latest()->take(10)->get();
    }

    public function getTransfersData()
    {
        return Transfer::with(['asset', 'user', 'status'])
            ->latest()
            ->paginate(10);
    }
}