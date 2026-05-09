<?php

namespace App\Services;

use App\Models\Transfer;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class TransferService
{
    /**
     * 1. STAFF WORKFLOW: Initiate a Return
     * Caleb uses this when he no longer needs an asset or is preparing it 
     * for a colleague (Return-to-Admin-to-User).
     */
    public function initiateReturn(array $data)
    {
        return DB::transaction(function () use ($data) {
            $asset = Asset::findOrFail($data['asset_id']);

            // Ensure the user actually owns the asset they are returning
            if ($asset->Employee_ID !== Auth::id()) {
                throw new Exception("You cannot return an asset that is not assigned to you.");
            }

            // Update Asset status to 'Pending Return'
            $pendingStatusId = Status::where('Status_Name', 'Pending Return')->first()?->id ?? 1;
            $asset->update(['Status_ID' => $pendingStatusId]);

            // Create the Transfer record (Return type)
            return Transfer::create([
                'asset_id' => $data['asset_id'],
                'sender_id' => Auth::id(),
                'receiver_id' => null, // Returning to the office/Admin
                'status' => 'pending_inspection',
                'sender_condition' => $data['sender_condition'], // Caleb's self-assessment
                'notes' => $data['notes'] ?? null,
                'type' => 'return'
            ]);
        });
    }

    /**
     * 2. ADMIN WORKFLOW: Complete Inspection (The Gateway)
     * Admin uses this when Caleb physically hands over the device.
     */
    public function processAdminInspection($transferId, array $data)
    {
        return DB::transaction(function () use ($transferId, $data) {
            $transfer = Transfer::findOrFail($transferId);
            $asset = Asset::findOrFail($transfer->asset_id);

            // Update the Transfer record with Admin's official findings
            $transfer->update([
                'status' => 'completed',
                'admin_condition' => $data['condition'], // Official State: Good, Damaged, etc.
                'missing_items' => $data['missing_items'] ?? [], // Array of missing parts
                'actioned_by' => Auth::id(),
                'actioned_at' => now(),
            ]);

            /**
             * Update Asset based on Admin's decision
             */
            $statusName = $data['asset_status'] ?? 'Ready to Deploy';
            // Normalize status names from UI if necessary
            if (strtolower($statusName) === 'available') $statusName = 'Ready to Deploy';
            
            $statusId = Status::where('Status_Name', $statusName)->first()?->id ?? 1;

            $asset->update([
                'Status_ID' => $statusId,
                'Employee_ID' => null, // Asset is officially unlinked from Caleb
            ]);

            return $transfer;
        });
    }

    /**
     * 3. ADMIN WORKFLOW: Check-Out (Assignment)
     * Admin selects a 'Ready to Deploy' asset and assigns it to Staff B.
     */
    public function assignAsset(array $data)
    {
        return DB::transaction(function () use ($data) {
            $asset = Asset::findOrFail($data['asset_id']);

            // Create a transfer record for the new user to verify
            return Transfer::create([
                'asset_id' => $asset->id,
                'sender_id' => Auth::id(), // Admin is the sender
                'receiver_id' => $data['receiver_id'],
                'status' => 'pending_verification', // Limbo state until Staff B clicks 'Accept'
                'admin_condition' => $asset->physical_condition ?? 'Good',
                'type' => 'assignment',
                'notes' => $data['notes'] ?? 'New assignment'
            ]);
        });
    }

    /**
     * 4. STAFF WORKFLOW: Inbound Verification
     * Staff B clicks "Accept" to officially take responsibility.
     */
    public function verifyInboundAssignment($transferId, $status)
    {
        return DB::transaction(function () use ($transferId, $status) {
            $transfer = Transfer::where('receiver_id', Auth::id())->findOrFail($transferId);
            $asset = Asset::findOrFail($transfer->asset_id);

            if ($status === 'accepted') {
                $transfer->update(['status' => 'deployed']);
                
                $deployedStatusId = Status::where('Status_Name', 'Deployed')->first()?->id ?? Status::where('Status_Name', 'Active')->first()?->id ?? 1;

                $asset->update([
                    'Status_ID' => $deployedStatusId,
                    'Employee_ID' => Auth::id(),
                ]);
            } else {
                $transfer->update(['status' => 'disputed']);
                // Asset remains with Admin/In Office status
            }

            return $transfer;
        });
    }
}