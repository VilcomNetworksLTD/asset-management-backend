<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserItemService
{
    public function getMyAccessories()
    {
        $user = Auth::user();
        // Only get accessories that have not been marked as returned
        return $user->accessories()->wherePivotNull('returned_at')->get();
    }

    public function getMyConsumables()
    {
        $user = Auth::user();
        return $user->consumables()->wherePivotNull('returned_at')->get();
    }

    public function getMyLicenses()
    {
        $user = Auth::user();
        return $user->licenses()->wherePivotNull('returned_at')->get();
    }

    public function getUserStats()
    {
        $user = Auth::user();

        $myAssetsCount = $user->assets()->count();
        // Assuming Status IDs 3 (Resolved) and 4 (Closed) mean the ticket is not open.
        $openTicketsCount = $user->tickets()->whereNotIn('Status_ID', [3, 4])->count();

        return [
            'my_assets_count' => $myAssetsCount,
            'open_tickets_count' => $openTicketsCount,
            
            // Use direct DB queries on pivot tables for an accurate sum of quantities
            'my_licenses_count' => $user->licenses()->wherePivotNull('returned_at')->count(),
            'my_accessories_count' => (int) DB::table('accessory_user')->where('user_id', $user->id)->whereNull('returned_at')->sum('quantity'),
            'my_consumables_count' => (int) DB::table('consumable_user')->where('user_id', $user->id)->whereNull('returned_at')->sum('quantity'),

            'recent_assets' => $user->assets()->latest()->take(5)->get(),
            'recent_licenses' => $user->licenses()->wherePivotNull('returned_at')->latest('license_user.created_at')->take(5)->get(),
            'recent_accessories' => $user->accessories()->wherePivotNull('returned_at')->latest('accessory_user.created_at')->take(5)->get(),
            'recent_consumables' => $user->consumables()->wherePivotNull('returned_at')->latest('consumable_user.created_at')->take(5)->get(),
        ];
    }
}