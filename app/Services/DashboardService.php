<?php

namespace App\Services;



use App\Models\{Asset, Transfer, User, License, Accessory, Consumable, Component, SslCertificate, Ticket};
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
        $components = Component::count();
        $users = User::count();
        $ssl_certificates = SslCertificate::count();
        $tickets = Ticket::count();

        return [
            // Legacy keys
            'total_assets'      => $assets,
            'total_licenses'    => $licenses,
            'total_accessories' => $accessories,
            'total_components'  => $components,
            'total_users'       => $users,
            'total_ssl_certificates' => $ssl_certificates,

            // Frontend-friendly keys
            'assets'            => $assets,
            'licenses'          => $licenses,
            'accessories'       => $accessories,
            'components'        => $components,
            'tickets'           => $tickets,
            'ssl_certificates'  => $ssl_certificates,
            'people'            => $users,

            'status_distribution' => [
                'ready_to_deploy' => Asset::whereIn('Status_ID', function($query) {
                    $query->select('id')->from('statuses')
                          ->whereIn('Status_Name', ['Ready to Deploy', 'Available', 'Ready']);
                })->count(),
                'deployed' => Asset::whereIn('Status_ID', function($query) {
                    $query->select('id')->from('statuses')
                          ->whereIn('Status_Name', ['Deployed', 'Assigned', 'In Use', 'Checked Out']);
                })->count(),
                'archived' => Asset::whereIn('Status_ID', function($query) {
                    $query->select('id')->from('statuses')
                          ->whereIn('Status_Name', ['Archived', 'Archive']);
                })->count(),
            ]
        ];
    }

    public function getTransfersData()
    {
         return Transfer::with(['asset', 'user', 'status'])
            ->latest()
            ->paginate(10);
    }
}