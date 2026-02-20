<?php

namespace App\Services;



use App\Models\{Asset, Transfer, User, License, Accessory, Consumable, Component};
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

        return [
            // Legacy keys
            'total_assets'      => $assets,
            'total_licenses'    => $licenses,
            'total_accessories' => $accessories,
            'total_consumables' => $consumables,
            'total_components'  => $components,
            'total_users'       => $users,

            // Frontend-friendly keys
            'assets'            => $assets,
            'licenses'          => $licenses,
            'accessories'       => $accessories,
            'consumables'       => $consumables,
            'components'        => $components,
            'people'            => $users,

            'status_distribution' => [
                'available' => Asset::where('Status_ID', 1)->count(),
                'pending'   => Asset::where('Status_ID', 2)->count(),
                'archived'  => Asset::where('Status_ID', 5)->count(),
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