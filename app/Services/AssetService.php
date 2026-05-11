<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Status;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AssetService
{
    protected $barcodeService;

    public function __construct(BarcodeService $barcodeService)
    {
        $this->barcodeService = $barcodeService;
    }

    public function getAllAssets()
    {
        return Asset::withTrashed()
            ->with(['status', 'supplier', 'user', 'category', 'locationModel'])
            ->latest()
            ->get();
    }

    public function store(array $data): Asset
    {
        return DB::transaction(function () use ($data) {
            $purchaseDate = $data['Purchase_Date'] ?? now()->toDateString();

            // 1. Default Status Logic
            $availableStatusId = Status::query()
                ->whereIn('Status_Name', ['Ready to Deploy', 'Available'])
                ->value('id');

            $data['Status_ID'] = $data['Status_ID'] ?? $availableStatusId ?? 1;

            // 2. Pricing Logic
            if (! empty($data['Price'])) {
                $data['depreciation_value'] = $data['Price'] * 0.10;
                $data['current_value'] = $data['Price'] - $data['depreciation_value'];
            } else {
                $data['depreciation_value'] = 0;
                $data['current_value'] = 0;
            }

            // 3. Backward Compatibility
            if (! isset($data['Asset_Category']) && isset($data['category_id'])) {
                $cat = Category::find($data['category_id']);
                if ($cat) {
                    $data['Asset_Category'] = $cat->name;
                }
            }

            // 4. Create Core Asset
            // Ensure 'custom_attributes' is part of the $data array passed here
            $asset = Asset::create($data);

            // 5. Generate & Save Barcode (VNL-000X)
            $asset->barcode = $this->barcodeService->generateUniqueBarcodeContent($asset->id, $asset->Asset_Category);
            $asset->save();

            // 6. Activity Logging
            ActivityLog::create([
                'asset_id' => $asset->id,
                'Employee_ID' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Created',
                'target_type' => 'Asset',
                'target_name' => $asset->Asset_Name,
                'details' => "Purchased: $purchaseDate | Barcode: {$asset->barcode}",
            ]);

            return $asset->load(['status', 'supplier', 'user', 'category', 'locationModel']);
        });
    }

    public function updateAsset(int $id, array $data): Asset
    {
        return DB::transaction(function () use ($id, $data) {
            $asset = Asset::findOrFail($id);

            if (isset($data['warranty_image_path'])) {
                $oldImagePath = $asset->warranty_image_path;
                if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            if (isset($data['Price'])) {
                $data['depreciation_value'] = $data['Price'] * 0.10;
                $data['current_value'] = $data['Price'] - $data['depreciation_value'];
            }

            // --- CRITICAL FIX FOR DYNAMIC FIELDS ---
            // If updating from a specific category form, ensure we don't wipe existing attributes
            if (isset($data['custom_attributes']) && is_array($data['custom_attributes'])) {
                $existingAttributes = $asset->custom_attributes ?? [];
                $data['custom_attributes'] = array_merge($existingAttributes, $data['custom_attributes']);
            }

            $asset->update($data);

            ActivityLog::create([
                'asset_id' => $asset->id,
                'Employee_ID' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'System',
                'action' => 'Updated',
                'target_type' => 'Asset',
                'target_name' => $asset->Asset_Name,
            ]);

            return $asset->fresh()->load(['status', 'supplier', 'user', 'category', 'locationModel']);
        });
    }

    // ... rest of your methods (delete, assign, etc.) remain the same
}
