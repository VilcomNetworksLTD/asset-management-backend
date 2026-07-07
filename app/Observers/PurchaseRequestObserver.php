<?php

namespace App\Observers;

use App\Models\PurchaseRequest;
use App\Models\ActivityLog;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestObserver
{
    public function created(PurchaseRequest $purchase): void
    {
        $user = Auth::user();

        // Resolve the linked asset (via ticket if available)
        $assetId = null;
        if ($purchase->ticket_id) {
            $ticket = Ticket::with('issue')->find($purchase->ticket_id);
            $assetId = $ticket?->issue?->Asset_ID;
        }

        ActivityLog::create([
            'asset_id'    => $assetId,
            'Employee_ID' => $user ? $user->id : $purchase->user_id,
            'user_name'   => $user ? $user->name : 'System',
            'action'      => 'Purchase Request Created',
            'target_type' => 'PurchaseRequest',
            'target_name' => $purchase->item_name,
            'details'     => "Purchase request created for: {$purchase->item_name} (Estimated Cost: " . ($purchase->estimated_cost ?? 'TBD') . ")"
        ]);
    }

    public function updated(PurchaseRequest $purchase): void
    {
        if ($purchase->isDirty('status')) {
            $user = Auth::user();

            // Resolve the linked asset (via ticket if available)
            $assetId = null;
            if ($purchase->ticket_id) {
                $ticket = Ticket::with('issue')->find($purchase->ticket_id);
                $assetId = $ticket?->issue?->Asset_ID;
            }

            $actionMap = [
                'escalated' => 'Purchase Request Escalated',
                'approved'  => 'Purchase Approved',
                'rejected'  => 'Purchase Rejected',
                'purchased' => 'Item Purchased',
            ];
            
            $statusLower = strtolower($purchase->status);
            $action = $actionMap[$statusLower] ?? 'Purchase Status Updated';
            
            $detailsMap = [
                'escalated' => "Purchase request escalated to management for budget approval: {$purchase->item_name}",
                'approved'  => "Management approved purchase for: {$purchase->item_name}",
                'rejected'  => "Management rejected purchase: " . ($purchase->rejection_reason ?? 'No reason provided'),
                'purchased' => "Item marked as acquired/purchased: {$purchase->item_name}",
            ];
            
            $details = $detailsMap[$statusLower] ?? "Purchase status updated to: {$purchase->status}";

            ActivityLog::create([
                'asset_id'    => $assetId,
                'Employee_ID' => $user ? $user->id : ($purchase->management_id ?? $purchase->user_id),
                'user_name'   => $user ? $user->name : 'System',
                'action'      => $action,
                'target_type' => 'PurchaseRequest',
                'target_name' => $purchase->item_name,
                'details'     => $details
            ]);
        }
    }
}
