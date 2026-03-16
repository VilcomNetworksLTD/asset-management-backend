<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Asset;
use App\Models\AssetConsumable;
use App\Models\Consumable;
use App\Models\Status;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    protected $assetService;

    public function __construct(AssetService $assetService)
    {
        $this->assetService = $assetService;
    }

    /**
     * Get assets for HOD's department staff.
     */
    public function hodDepartmentAssets(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || strtolower($user->role) !== 'hod' || !$user->department_id) {
            return response()->json(['error' => 'Unauthorized or not assigned to a department.'], 403);
        }

        $departmentStaff = User::with([
                'assets' => function ($query) {
                    $query->with(['status:id,Status_Name', 'specs']);
                }
            ])
            ->where('department_id', $user->department_id)
            ->where('id', '!=', $user->id) 
            ->select('id', 'name', 'email')
            ->get();

        return response()->json($departmentStaff);
    }

    /**
     * Paginated list of assets for Admin UI.
     */
    public function list(Request $request): JsonResponse
    {
        $query = Asset::with(['status', 'supplier', 'user', 'specs']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('Asset_Name', 'like', "%{$search}%")
                    ->orWhere('Serial_No', 'like', "%{$search}%")
                    ->orWhere('Asset_Category', 'like', "%{$search}%");
            });
        }

        $perPage = $request->integer('per_page', 15);
        return response()->json($query->latest()->paginate($perPage));
    }

    /**
     * Basic index for users (their own assets) or admins (available assets).
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if ($user && ($user->role ?? 'user') !== 'admin') {
            $assets = Asset::with(['status', 'supplier', 'user'])
                ->where('Employee_ID', $user->id)
                ->latest()
                ->get();
            return response()->json($assets);
        }

        $assets = Asset::with(['status', 'supplier', 'user'])
            ->where(function ($q) {
                $q->whereNull('Employee_ID')
                  ->orWhereHas('status', function ($sq) {
                      $sq->whereIn('Status_Name', ['Available', 'Ready to Deploy']);
                  });
            })
            ->latest()
            ->get();

        return response()->json($assets);
    }

    /**
     * Store a newly created asset.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Asset_Name' => 'required|string|max:255',
            'Asset_Category' => 'required|string|max:255',
            'Serial_No' => 'nullable|string|max:255|unique:assets,Serial_No',
            'Supplier_ID' => 'required|integer|exists:suppliers,id',
            'Employee_ID' => 'nullable|integer|exists:users,id',
            'Status_ID' => 'nullable|integer|exists:statuses,id',
            'Price' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'Purchase_Date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date',
            'warranty_image'  => 'nullable|image|max:10240',
            'processor' => 'nullable|string|max:255',
            'memory' => 'nullable|string|max:255',
            'storage_type' => 'nullable|string|max:255',
            'storage_capacity' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'mac_address' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:255',
        ]);
        
        if ($request->hasFile('warranty_image')) {
            $path = $request->file('warranty_image')->store('warranty_images', 'public');
            $data['warranty_image_path'] = $path;
        }

        $asset = $this->assetService->store($data);
        return response()->json($asset, 201);
    }

    /**
     * Show detailed asset info with logs and history.
     */
    public function show($id): JsonResponse
    {
        $asset = Asset::with([
            'user', 
            'supplier', 
            'status', 
            'assignments.user', 
            'specs',
            'activityLogs' => function ($query) {
                $query->latest();
            }
        ])->findOrFail($id);

        $isNameUnique = Asset::where('Asset_Name', $asset->Asset_Name)->count() === 1;

        if ($isNameUnique) {
            $legacyLogs = ActivityLog::whereNull('asset_id')
                ->where('target_type', 'Asset')
                ->where('target_name', $asset->Asset_Name)
                ->latest()
                ->get();

            if ($legacyLogs->isNotEmpty()) {
                $allLogs = $asset->activityLogs->merge($legacyLogs)->sortByDesc('created_at');
                $asset->setRelation('activityLogs', $allLogs);
            }
        }

        return response()->json($asset);
    }

    public function fetchPreAssets($id)
    {
        $asset = $this->assetService->fetchPreAssets($id);
        return response()->json($asset);
    }

    /**
     * Update asset details.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'Asset_Name' => 'sometimes|required|string|max:255',
            'Asset_Category' => 'sometimes|required|string|max:255',
            'Serial_No' => 'sometimes|nullable|string|max:255|unique:assets,Serial_No,' . $id,
            'Supplier_ID' => 'sometimes|required|integer|exists:suppliers,id',
            'Employee_ID' => 'nullable|integer|exists:users,id',
            'Status_ID' => 'nullable|integer|exists:statuses,id',
            'Price' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'Purchase_Date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date',
            'warranty_image'  => 'nullable|image|max:10240',
            'processor' => 'nullable|string|max:255',
            'memory' => 'nullable|string|max:255',
            'storage_type' => 'nullable|string|max:255',
            'storage_capacity' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'mac_address' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('warranty_image')) {
            $path = $request->file('warranty_image')->store('warranty_images', 'public');
            $data['warranty_image_path'] = $path;
        }

        $asset = $this->assetService->updateAsset($id, $data);
        return response()->json($asset);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->assetService->deleteAsset($id);
        return response()->json(['message' => 'Asset deleted successfully']);
    }

    public function assign(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $this->assetService->assignAsset($id, $request->user_id);
        return response()->json(['message' => 'Asset assigned successfully']);
    }

    /**
     * Lifecycle tracking for toners/ink (Continuous replacement cycle)
     */
    public function replaceToner(Request $request, $printerId): JsonResponse
    {
        $data = $request->validate([
            'consumable_id' => 'required|exists:consumables,id',
            'color' => 'required|string' 
        ]);

        return DB::transaction(function () use ($printerId, $data) {
            $printer = Asset::findOrFail($printerId);
            $consumable = Consumable::findOrFail($data['consumable_id']);

            // 1. Ensure we have ink in stock
            if ($consumable->in_stock < 1) {
                return response()->json(['message' => 'Not enough ink in stock.'], 422);
            }

            // 2. Mark the currently active toner of this color as finished (depleted)
            AssetConsumable::where('asset_id', $printerId)
                ->where('color', $data['color'])
                ->whereNull('depleted_at')
                ->update(['depleted_at' => now()]);

            // 3. Start the clock on the new cartridge
            AssetConsumable::create([
                'asset_id' => $printer->id,
                'consumable_id' => $consumable->id,
                'color' => $data['color'],
                'installed_at' => now(),
                'installed_by' => Auth::id(),
            ]);

            // 4. Deduct 1 from central inventory
            $consumable->decrement('in_stock', 1);

            return response()->json(['message' => "{$data['color']} toner replaced successfully!"]);
        });
    }

    /**
     * Get history of toner replacements for a specific printer.
     */
    public function getTonerHistory($id): JsonResponse
    {
        $history = AssetConsumable::with(['consumable', 'user'])
            ->where('asset_id', $id)
            ->latest('installed_at')
            ->get();

        return response()->json($history);
    }
}