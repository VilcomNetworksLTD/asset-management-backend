<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Models\Asset;
use App\Models\User;
use App\Models\Ticket;
use App\Models\ActivityLog;
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
            'recentActivity' => ActivityLog::latest()->take(5)->get()
        ]);
    }

    // Otherwise, show the User Dashboard
    return Inertia::render('UserDashboard', [
        'stats' => $this->dashboardService->getStats(),
        'myAssets' => Asset::where('Employee_ID', $user->id)->get(),
        'recentActivity' => ActivityLog::where('Employee_ID', $user->id)->latest()->take(5)->get()
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

    // Assets and Tickets are already using Employee_ID
    $assets = Asset::with('status')
        ->where('Employee_ID', $user->id)
        ->select('id', 'Asset_Name as model', 'Serial_No as serial', 'Asset_Category as category', 'Status_ID')
        ->get()
        ->map(function ($asset) {
            $asset->asset_tag = 'AST-' . str_pad((string) $asset->id, 4, '0', STR_PAD_LEFT);
            $asset->status_name = $asset->status->Status_Name ?? 'Deployed';
            return $asset;
        });

    $openTicketsCount = Ticket::where('Employee_ID', $user->id)
        ->where('Status_ID', '!=', 3)
        ->count();

    // FIXED: Now uses Employee_ID instead of the missing User_ID
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
        'my_assets_count' => $assets->count(),
        'open_tickets_count' => $openTicketsCount,
        'recent_assets' => $assets->take(5),
        'logs' => $logs
    ]);
}
    private function getStatusColor($type)
    {
        return match(strtolower($type ?? '')) {
            'requested', 'pending' => 'bg-orange-500',
            'update', 'edit'       => 'bg-blue-500',
            'checkout', 'deployed' => 'bg-green-500',
            default                => 'bg-gray-400',
        };
    }
}