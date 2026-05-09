<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Models\Asset;
use App\Models\User;
use App\Models\Ticket;
use App\Models\ActivityLog;
use App\Models\Accessory;
use App\Models\Consumable;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Admin Dashboard Entry Point
     */
public function index(Request $request)
{
    $user = $request->user();

    // Check the 'role' column for 'admin'
    if ($user->role === 'admin') { 
        return Inertia::render('AdminDashboard', [
            'stats' => $this->dashboardService->getStats(),
            'recentActivity' => ActivityLog::latest()->take(5)->get()->map(fn($log) => [
                'id' => $log->id,
                'type' => $log->action,
                'message' => $log->details ?? "{$log->user_name} {$log->action} {$log->target_type}: {$log->target_name}",
                'time' => $log->created_at->diffForHumans(),
                'color' => $this->getStatusColor($log->action)
            ])
        ]);
    }

    // Otherwise, show the User Dashboard
    return Inertia::render('UserDashboard', [
        'stats' => $this->dashboardService->getStats(),
        'myAssets' => Asset::with(['status', 'category'])->where('Employee_ID', $user->id)->get(),
        'recentActivity' => ActivityLog::where('Employee_ID', $user->id)->latest()->take(5)->get()->map(fn($log) => [
            'id' => $log->id,
            'type' => $log->action,
            'message' => $log->details ?? "{$log->action} {$log->target_type}: {$log->target_name}",
            'time' => $log->created_at->diffForHumans(),
            'color' => $this->getStatusColor($log->action)
        ])
    ]);
}

    /**
     * JSON API for dynamic refreshes
     */
    public function getStats()
    {
        return response()->json($this->dashboardService->getStats());
    }

    public function getUserAssets(Request $request)
{
    $user = $request->user();

    $assetsQuery = Asset::with(['status', 'category'])
        ->where('Employee_ID', $user->id)
        ->get();

    $recentAssetsData = [];
    foreach ($assetsQuery as $asset) {
        $recentAssetsData[] = [
            'id' => $asset->id,
            'asset_tag' => $asset->barcode,
            'model' => $asset->Asset_Name,
            'serial' => $asset->Serial_No,
            'category' => $asset->category ? $asset->category->name : $asset->Asset_Category,
            'category_obj' => $asset->category,
            'status_name' => optional($asset->status)->Status_Name ?? 'Deployed',
            'purchase_date' => $asset->Purchase_Date,
        ];
    }

    $openTicketsCount = Ticket::where('Employee_ID', $user->id)
        ->where('Status_ID', '!=', 3)
        ->count();

    // Fetch all other assigned items
    $myLicenses = $user->licenses()->wherePivotNull('returned_at')->get();
    $myAccessories = $user->accessories()->withPivot('quantity')->wherePivotNull('returned_at')->get();
    

    // Calculate total quantities for items that have them
    $myAccessoriesCount = $myAccessories->sum(fn($i) => $i->pivot->quantity);
   

    $logs = ActivityLog::where('Employee_ID', $user->id)
        ->latest()
        ->take(5)
        ->get()
        ->map(function($log) {
            return [
                'id' => $log->id,
                'type' => $log->action, // Matches 'action' column
                'message' => $log->details, // Matches 'details' column
                'time' => $log->created_at->diffForHumans(),
                'color' => $this->getStatusColor($log->action)
            ];
        });

    return response()->json([
        'my_assets_count' => count($recentAssetsData),
        'open_tickets_count' => $openTicketsCount,
        'recent_assets' => array_slice($recentAssetsData, 0, 5),
        'logs' => $logs,
        'my_licenses_count' => $myLicenses->count(),
        'my_accessories_count' => $myAccessoriesCount,
        'recent_licenses' => $myLicenses->take(5),
        'recent_accessories' => $myAccessories->take(5),
        
    ]);
}
    private function getStatusColor($type)
    {
        $type = strtolower($type ?? '');
        
        if (str_contains($type, 'resolved') || str_contains($type, 'verified') || str_contains($type, 'deployed') || str_contains($type, 'accepted') || str_contains($type, 'assigned')) return 'bg-green-500';
        if (str_contains($type, 'requested') || str_contains($type, 'pending') || str_contains($type, 'alert')) return 'bg-orange-500';
        if (str_contains($type, 'created') || str_contains($type, 'updated') || str_contains($type, 'scanned') || str_contains($type, 'discovered')) return 'bg-blue-500';
        if (str_contains($type, 'rejected') || str_contains($type, 'deleted') || str_contains($type, 'disputed')) return 'bg-red-500';

        return 'bg-gray-400';
    }
}