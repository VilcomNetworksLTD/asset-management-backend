<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\ActivityLog;
use App\Models\Status;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;

class AssetService
{
    
    public function getAllAssets()
    {
        return Asset::with('status')->get();
    }

    
    public function createAsset(array $data)
    {
        return Asset::create($data);
    }

    
    public function store(array $data): Asset
    {
        $purchaseDate = $data['Purchase_Date'] ?? null;
        unset($data['Purchase_Date']);

        
        $availableStatusId = Status::query()
            ->whereIn('Status_Name', ['Available', 'Ready to Deploy'])
            ->value('id');

        $data['Status_ID'] = $availableStatusId ?? ($data['Status_ID'] ?? 1);

        $asset = Asset::create($data);

        $supplierName = Supplier::query()
            ->where('id', $asset->Supplier_ID)
            ->value('Supplier_Name') ?? "Supplier #{$asset->Supplier_ID}";

        ActivityLog::create([
            'Employee_ID' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Created',
            'target_type' => 'Asset',
            'target_name' => $asset->Asset_Name,
            'details' => 'Supplier: ' . $supplierName
                . '; Purchase Date: ' . ($purchaseDate ?: now()->toDateString())
                . '; Status: ' . ($availableStatusId ? 'Available/Ready to Deploy' : 'Default Available'),
        ]);

        return $asset->load('status');
    }
    public function getAssetById(int $id): Asset
    {
        return Asset::with(['status', 'supplier'])->findOrFail($id);
    }

    
    public function updateAsset(int $id, array $data): Asset
    {
        $asset = Asset::findOrFail($id);
        $asset->update($data);

        ActivityLog::create([
            'Employee_ID' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Updated',
            'target_type' => 'Asset',
            'target_name' => $asset->Asset_Name,
            'details' => 'Asset details updated.',
        ]);

        return $asset->load('status');
    }

    
    public function deleteAsset(int $id): bool
    {
        $asset = Asset::findOrFail($id);
        
        ActivityLog::create([
            'Employee_ID' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Deleted',
            'target_type' => 'Asset',
            'target_name' => $asset->Asset_Name,
            'details' => 'Asset removed from system.',
        ]);

        return $asset->delete();
    }
}