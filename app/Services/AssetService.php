<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Status;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AssetService
{
    /**
     * Get all assets with common relationships.
     */
    public function getAllAssets()
    {
        return Asset::withTrashed() // <-- include soft-deleted
            ->with(['status', 'supplier', 'user'])
            ->latest()
            ->get();
    }

    /**
     * Centralized Store Logic
     * Handles Status defaults, Price calculations, and Activity Logging.
     */
    public function store(array $data): Asset
    {
        $purchaseDate = $data['Purchase_Date'] ?? now()->toDateString();
        
        // Logic for default Status
        $availableStatusId = Status::query()
            ->whereIn('Status_Name', ['Available', 'Ready to Deploy'])
            ->value('id');

        $data['Status_ID'] = $data['Status_ID'] ?? $availableStatusId ?? 1;

        // Auto-calculate values if Price is provided
        if (!empty($data['Price'])) {
            $data['depreciation_value'] = $data['Price'] * 0.10;
            $data['current_value'] = $data['Price'] - $data['depreciation_value'];
        } else {
            $data['depreciation_value'] = 0;
            $data['current_value'] = 0;
        }

        $asset = Asset::create($data);

        // Create specs (Laravel will automatically filter $data to only include fillable fields for AssetSpec)
        $asset->specs()->create($data);

    
        // Activity Logging
        ActivityLog::create([
            'asset_id'    => $asset->id,
            'Employee_ID' => Auth::id(),
            'user_name'   => Auth::user()->name ?? 'System',
            'action'      => 'Created',
            'target_type' => 'Asset',
            'target_name' => $asset->Asset_Name,
            'details'     => "Purchased: $purchaseDate",
        ]);

        return $asset->load(['status', 'supplier', 'user', 'specs']);
    }

    /**
     * Fetch a single asset.
     */
    public function getAssetById(int $id): Asset
    {
        return Asset::with(['status', 'supplier', 'user'])->findOrFail($id);
    }

    /**
     * Update Asset Logic
     * Recalculates values if Price is changed and logs the update.
     */
    public function updateAsset(int $id, array $data): Asset
    {
        $asset = Asset::findOrFail($id);

        // If a new warranty image is being uploaded...
        if (isset($data['warranty_image_path'])) {
            // Get the path of the old image.
            $oldImagePath = $asset->warranty_image_path;

            // If an old image exists, delete it from storage.
            if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }
        }

        if (isset($data['Price'])) {
            $data['depreciation_value'] = $data['Price'] * 0.10;
            $data['current_value'] = $data['Price'] - $data['depreciation_value'];
        }

        $asset->update($data);

        // Update or Create specs
        $asset->specs()->updateOrCreate(
            ['asset_id' => $asset->id],
            $data
        );

        ActivityLog::create([

            'asset_id'    => $asset->id,
            'Employee_ID' => Auth::id(),
            'user_name'   => Auth::user()->name ?? 'System',
            'action'      => 'Updated',
            'target_type' => 'Asset',
            'target_name' => $asset->Asset_Name,

        ]);

        return $asset->fresh()->load(['status', 'supplier', 'user', 'specs']);
    }

    // function to nfetch prev asset users
    public function fetchPreAssets($id){
        

         return ActivityLog::where('asset_id', $id)
                      ->orderBy('created_at', 'desc')
                      ->get();

    }

    /**
     * Delete Asset Logic
     */
    public function deleteAsset(int $id): bool
    {
        $asset = Asset::findOrFail($id);
        $assetName = $asset->Asset_Name;
        
        $deleted = $asset->delete();

        if ($deleted) {
            ActivityLog::create([
                'asset_id'    => $id,
                'Employee_ID' => Auth::id(),
                'user_name'   => Auth::user()->name ?? 'System',
                'action'      => 'Deleted',
                'target_type' => 'Asset',
                'target_name' => $assetName,
            ]);
        }

        return $deleted;
    }

    public function assignAsset(int $id, int $userId)
    {
        $asset = Asset::findOrFail($id);
        $user = User::findOrFail($userId);
        $deployedStatus = Status::whereIn('Status_Name', ['Deployed', 'Assigned', 'In Use'])->first();
        
        $asset->update([
            'Employee_ID' => $userId,
            'Status_ID' => $deployedStatus ? $deployedStatus->id : $asset->Status_ID,
        ]);

        ActivityLog::create([
            'asset_id'    => $asset->id,
            'Employee_ID' => Auth::id(),
            'user_name'   => Auth::user()->name ?? 'System',
            'action'      => 'Assigned',
            'target_type' => 'Asset',
            'target_name' => $asset->Asset_Name,
            'details'     => "Assigned to: {$user->name} (ID: {$user->id})",
        ]);

        return $asset;
    }
}