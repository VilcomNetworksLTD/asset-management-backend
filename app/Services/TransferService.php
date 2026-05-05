<?php

namespace App\Services;

use App\Models\Transfer;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Mail\GeneralOperationalMail;
use Illuminate\Support\Facades\Mail;

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
            if ($asset->assigned_to !== Auth::id()) {
                throw new Exception("You cannot return an asset that is not assigned to you.");
            }

            // Update Asset status to 'Pending Return'
            $asset->update(['status' => 'Pending Return']);

            // Create the Transfer record (Return type)
            $transfer = Transfer::create([
                'asset_id' => $data['asset_id'],
                'sender_id' => Auth::id(),
                'receiver_id' => null, // Returning to the office/Admin
                'status' => 'pending_inspection',
                'sender_condition' => $data['sender_condition'], // Caleb's self-assessment
                'notes' => $data['notes'] ?? null,
                'type' => 'return'
            ]);

            \App\Models\ActivityLog::create([
                'asset_id' => $asset->id,
                'Employee_ID' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'Return Initiated',
                'target_name' => $asset->Asset_Name,
                'details' => "Asset return process started by user."
            ]);

            return $transfer;
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
             * Update Asset based on Admin's decision:
             * 'Ready to Deploy' -> Asset is clean and stays in office.
             * 'Out for Repair' -> Sent to technician.
             * 'Archived/Lost'  -> Asset is gone/destroyed.
             */
            $asset->update([
                'status' => $data['asset_status'],
                'physical_condition' => $data['condition'],
                'assigned_to' => null, // Asset is officially unlinked from Caleb
                'last_inspection_date' => now()
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
            $transfer = Transfer::create([
                'asset_id' => $asset->id,
                'sender_id' => Auth::id(), // Admin is the sender
                'receiver_id' => $data['receiver_id'],
                'status' => 'pending_verification', // Limbo state until Staff B clicks 'Accept'
                'admin_condition' => $asset->physical_condition,
                'type' => 'assignment',
                'notes' => $data['notes'] ?? 'New assignment'
            ]);

            // Requirement 2: Notify All Relevant Parties (Sender/Admin and Receiver)
            $receiver = \App\Models\User::find($data['receiver_id']);
            $admin = Auth::user();

            // Notify the Receiver
            if ($receiver && $receiver->email) {
                Mail::to($receiver->email)->send(new GeneralOperationalMail(
                    $receiver,
                    "Equipment Assigned: {$asset->Asset_Name}",
                    'New Assignment',
                    "A {$asset->Asset_Name} has been assigned to you. Please log in to verify the condition and accept the item.",
                    [
                        ['label' => 'Item', 'value' => $asset->Asset_Name],
                        ['label' => 'Serial Number', 'value' => $asset->Serial_No],
                        ['label' => 'Assigned By', 'value' => $admin->name],
                    ],
                    'Accept Asset',
                    config('app.url') . '/dashboard/user/workspace'
                ));
            }

            // Notify the Admin/Superadmin (Confirmation)
            $admins = \App\Models\User::whereIn('role', ['admin', 'superadmin'])->get()->filter(fn($u) => $u->email);
            foreach ($admins as $adm) {
                Mail::to($adm->email)->send(new GeneralOperationalMail(
                    $adm,
                    "Asset Assignment Initiated",
                    'Transfer Logged',
                    "The assignment of {$asset->Asset_Name} to {$receiver->name} has been initiated and is awaiting their acceptance.",
                    [
                        ['label' => 'Recipient', 'value' => $receiver->name],
                        ['label' => 'Equipment', 'value' => $asset->Asset_Name],
                    ],
                    'Track Movements',
                    config('app.url') . '/dashboard/admin/movements'
                ));
            }

            // Log activity
            \App\Models\ActivityLog::create([
                'asset_id' => $asset->id,
                'Employee_ID' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'Assigned',
                'target_name' => $asset->Asset_Name,
                'details' => "Assigned to {$receiver->name}"
            ]);

            return $transfer;
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

                $asset->update([
                    'status' => 'Deployed',
                    'assigned_to' => Auth::id(),
                    'last_checkout_date' => now()
                ]);
            } else {
                $transfer->update(['status' => 'disputed']);
                // Asset remains with Admin/In Office status
            }

            return $transfer;
        });
    }
}