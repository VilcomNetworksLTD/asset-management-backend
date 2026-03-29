<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\Status;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PurchaseRequestController extends Controller
{
    public function index(): JsonResponse
    {
        $requests = PurchaseRequest::with(['requester', 'management', 'ticket.user', 'maintenance.asset.user'])
            ->latest()
            ->get();
        return response()->json($requests);
    }

    /**
     * Admin API: Get all purchase requests for admin review
     */
    public function adminIndex(): JsonResponse
    {
        $requests = PurchaseRequest::with(['requester', 'management', 'ticket.user', 'maintenance.asset.user'])
            ->latest()
            ->get();
        return response()->json($requests);
    }

    /**
     * Admin API: Escalate a purchase request to management
     */
    public function escalateToManagement(Request $request, int $id): JsonResponse
    {
        $purchase = PurchaseRequest::with(['requester'])->findOrFail($id);
        
        $data = $request->validate([
            'estimated_cost' => 'nullable|numeric',
            'notes' => 'nullable|string'
        ]);

        $purchase->update([
            'status' => 'escalated',
            'estimated_cost' => $data['estimated_cost'] ?? $purchase->estimated_cost,
            'notes' => $data['notes'] ?? $purchase->notes
        ]);

        // Log activity
        ActivityLog::create([
            'Employee_ID' => Auth::id(),
            'user_name' => Auth::user()->name,
            'action' => 'Purchase Request Escalated',
            'target_type' => 'PurchaseRequest',
            'target_name' => $purchase->item_name,
            'details' => "Admin escalated purchase request to management for budget approval: {$purchase->item_name}"
        ]);

        // Email management
        $managers = User::where('role', 'management')->get();
        foreach ($managers as $manager) {
            if ($manager->email) {
                Mail::raw(
                    "A new purchase request requires your approval.\n\n" .
                    "Item: {$purchase->item_name}\n" .
                    "Requested by: {$purchase->requester->name}\n" .
                    "Estimated Cost: " . ($data['estimated_cost'] ?? 'Not specified') . "\n\n" .
                    "Please review and approve or reject this request.",
                    function ($m) use ($manager, $purchase) {
                        $m->to($manager->email)
                          ->subject("Action Required: Purchase Approval - {$purchase->item_name}");
                    }
                );
            }
        }

        // Email the requester that their request has been escalated
        if ($purchase->requester && $purchase->requester->email) {
            Mail::raw(
                "Your acquisition request for '{$purchase->item_name}' has been reviewed by IT Administration and escalated to Management for budget approval. " .
                "You will be notified once Management makes a decision.",
                function ($m) use ($purchase) {
                    $m->to($purchase->requester->email)
                      ->subject("Your Request Escalated: {$purchase->item_name}");
                }
            );
        }

        return response()->json(['message' => 'Request escalated to management.', 'purchase' => $purchase]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_name' => 'required|string|max:255',
            'estimated_cost' => 'nullable|numeric',
            'reason' => 'required|string',
            'ticket_id' => 'nullable|exists:tickets,id',
            'maintenance_id' => 'nullable|exists:maintenances,id',
            'requester_id' => 'required|exists:users,id',
        ]);

        $purchase = PurchaseRequest::create([
            ...$data,
            'requested_by_id' => Auth::id(),
            'status' => 'pending'
        ]);

        return response()->json($purchase, 201);
    }

    /**
     * Specific endpoint for escalating from maintenance.
     */
    public function escalateFromMaintenance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'maintenance_id' => 'required|exists:maintenances,id',
            'item_name' => 'required|string|max:255',
            'estimated_cost' => 'nullable|numeric',
            'reason' => 'required|string',
        ]);

        $maintenance = \App\Models\Maintenance::with('asset')->findOrFail($data['maintenance_id']);

        $purchase = PurchaseRequest::create([
            'maintenance_id' => $maintenance->id,
            'requester_id' => $maintenance->asset ? $maintenance->asset->Employee_ID : Auth::id(),
            'requested_by_id' => Auth::id(),
            'item_name' => $data['item_name'],
            'estimated_cost' => $data['estimated_cost'],
            'reason' => $data['reason'],
            'status' => 'pending'
        ]);

        // Notify Management
        $managers = User::where('role', 'management')->get();
        foreach ($managers as $manager) {
            if ($manager->email) {
                Mail::raw("A new purchase request for '{$purchase->item_name}' (Maintenance ID: {$maintenance->id}) requires your approval.", function ($m) use ($manager) {
                    $m->to($manager->email)->subject("Action Required: Maintenance Purchase Request");
                });
            }
        }

        return response()->json($purchase, 201);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $purchase = PurchaseRequest::with(['requester', 'initiator'])->findOrFail($id);
        
        DB::transaction(function () use ($purchase) {
            $purchase->update([
                'status' => 'approved',
                'approver_id' => Auth::id(),
                'approved_at' => now()
            ]);

            ActivityLog::create([
                'Employee_ID' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'Purchase Approved',
                'target_type' => 'PurchaseRequest',
                'target_name' => $purchase->item_name,
                'details' => "Management approved purchase for: {$purchase->item_name}"
            ]);
        });

        // Notify Staff and Admin
        $recipients = array_filter([$purchase->requester?->email, $purchase->initiator?->email]);
        foreach ($recipients as $email) {
            Mail::raw("The purchase request for '{$purchase->item_name}' has been APPROVED by management. Procurement can now proceed.", function ($m) use ($email, $purchase) {
                $m->to($email)->subject("Purchase Request Approved: {$purchase->item_name}");
            });
        }

        return response()->json(['message' => 'Purchase request approved.', 'purchase' => $purchase]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $purchase = PurchaseRequest::with(['requester', 'initiator'])->findOrFail($id);
        $data = $request->validate(['rejection_reason' => 'required|string']);

        DB::transaction(function () use ($purchase, $data) {
            $purchase->update([
                'status' => 'rejected',
                'approver_id' => Auth::id(),
                'notes' => $data['rejection_reason'],
                'approved_at' => now()
            ]);

            ActivityLog::create([
                'Employee_ID' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'Purchase Rejected',
                'target_type' => 'PurchaseRequest',
                'target_name' => $purchase->item_name,
                'details' => "Management rejected purchase: {$data['rejection_reason']}"
            ]);
        });

        // Notify Staff and Admin
        $recipients = array_filter([$purchase->requester?->email, $purchase->initiator?->email]);
        foreach ($recipients as $email) {
            Mail::raw("The purchase request for '{$purchase->item_name}' was rejected. Reason: {$data['rejection_reason']}", function ($m) use ($email, $purchase) {
                $m->to($email)->subject("Purchase Request Rejected: {$purchase->item_name}");
            });
        }

        return response()->json(['message' => 'Purchase request rejected.']);
    }
}
