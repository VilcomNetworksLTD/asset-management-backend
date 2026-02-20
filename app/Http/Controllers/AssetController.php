<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssetController extends Controller
{
    protected $assetService;

    public function __construct(AssetService $assetService)
    {
        $this->assetService = $assetService;
    }

    public function index(): JsonResponse
    {
        $user = Auth::user();

        // If not admin, only show assets assigned to them
        if ($user && ($user->role ?? 'user') !== 'admin') {
            $assets = Asset::with('status')
                ->where('Employee_ID', $user->id)
                ->latest()
                ->get();

            return response()->json($assets);
        }

        return response()->json($this->assetService->getAllAssets());
    }

    public function list(Request $request): JsonResponse
    {
        $query = Asset::with('status');
        $user = $request->user() ?? Auth::user();

        if ($user && ($user->role ?? 'user') !== 'admin') {
            $query->where('Employee_ID', $user->id);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('Asset_Name', 'like', "%{$search}%")
                    ->orWhere('Asset_Category', 'like', "%{$search}%")
                    ->orWhere('Serial_No', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('Asset_Category', $category);
        }

        if ($statusId = $request->integer('status_id')) {
            $query->where('Status_ID', $statusId);
        }

        $perPage = max(1, min(100, $request->integer('per_page', 10)));
        $assets = $query->latest()->paginate($perPage);

        return response()->json($assets);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Asset_Name'       => 'required|string|max:255',
            'Asset_Category'   => 'required|string|max:255',
            'Serial_No'        => 'nullable|string|max:255|unique:assets,Serial_No',
            'Supplier_ID'      => 'required|integer|exists:suppliers,id',
            'Employee_ID'      => 'required|integer|exists:users,id',
            'Status_ID'        => 'nullable|integer|exists:statuses,id',
            'Warranty_Details' => 'nullable|string',
            'License_Info'     => 'nullable|string',
            'Price'            => 'nullable|numeric|min:0',
            'Issue_ID'         => 'nullable|integer',
            'Purchase_Date'    => 'nullable|date',
        ]);

        $asset = $this->assetService->store($data);

        return response()->json($asset, 201);
    }

    /**
     * Display a specific asset.
     */
    public function show(int $id): JsonResponse
    {
        $asset = $this->assetService->getAssetById($id);
        return response()->json($asset);
    }

    /**
     * Update an existing asset using the Service.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'Asset_Name'       => 'sometimes|required|string|max:255',
            'Asset_Category'   => 'sometimes|required|string|max:255',
            'Serial_No'        => "nullable|string|max:255|unique:assets,Serial_No,{$id}",
            'Supplier_ID'      => 'sometimes|required|integer|exists:suppliers,id',
            'Employee_ID'      => 'sometimes|required|integer|exists:users,id',
            'Status_ID'        => 'sometimes|required|integer|exists:statuses,id',
            'Warranty_Details' => 'nullable|string',
            'License_Info'     => 'nullable|string',
            'Price'            => 'nullable|numeric|min:0',
            'Issue_ID'         => 'nullable|integer',
        ]);

        // Use the Service to update and trigger Activity Logging
        $asset = $this->assetService->updateAsset($id, $data);

        return response()->json($asset);
    }

    /**
     * Delete an asset using the Service.
     */
    public function destroy(int $id): JsonResponse
    {
        // Use the Service to delete and trigger Activity Logging
        $this->assetService->deleteAsset($id);

        return response()->json(['message' => 'Asset deleted successfully']);
    }
}