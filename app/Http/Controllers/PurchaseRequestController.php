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
use App\Traits\SendsDetailedEmails;

class PurchaseRequestController extends Controller
{
    use SendsDetailedEmails;

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
        if ($managers->isNotEmpty()) {
            $this->sendDetailedEmail(
                $managers,
                "Purchase Approval Required",
                "Action Required: Expenditure Authorization",
                "A new purchase request has been escalated for your approval.",
                [
                    "Item" => $purchase->item_name,
                    "Requested By" => $purchase->requester->name,
                    "Estimated Cost" => ($data['estimated_cost'] ?? false) ? "KSh " . $data['estimated_cost'] : 'Not specified',
                    "Reason" => $purchase->description ?? 'N/A',
                ],
                "Review & Approve",
                config('app.url') . "/dashboard/management/purchases"
            );
        }

        // Email the requester
        if ($purchase->requester) {
            $this->sendDetailedEmail(
                $purchase->requester,
                "Request Escalated to Management",
                "Acquisition Progress",
                "Your request for '{$purchase->item_name}' has been reviewed by IT Administration and escalated to Management for budget approval.",
                [
                    "Item" => $purchase->item_name,
                    "Current Status" => "Awaiting Management Approval",
                ],
                "Track Request",
                config('app.url') . "/dashboard/user/workspace"
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
        if ($managers->isNotEmpty()) {
            $this->sendDetailedEmail(
                $managers,
                "Maintenance Purchase Request",
                "Expenditure Authorization",
                "A maintenance-related purchase request has been created.",
                [
                    "Item" => $purchase->item_name,
                    "Estimated Cost" => $purchase->estimated_cost ? "KSh " . $purchase->estimated_cost : 'TBD',
                    "Maintenance Source" => "Asset Maintenance ID #{$maintenance->id}",
                    "Asset" => $maintenance->asset?->Asset_Name ?? 'Unknown',
                    "Reason" => $data['reason'],
                ],
                "Review Purchase",
                config('app.url') . "/dashboard/management/purchases"
            );
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
        $recipients = User::whereIn('id', array_filter([$purchase->requester_id, $purchase->requested_by_id]))->get();
        $this->sendDetailedEmail(
            $recipients,
            "Purchase Request Approved",
            "Acquisition Authorized",
            "The purchase request for '{$purchase->item_name}' has been APPROVED by management.",
            [
                "Item" => $purchase->item_name,
                "Approved By" => Auth::user()->name,
                "Status" => "Authorized for Procurement",
            ],
            "View Order Details",
            config('app.url') . "/dashboard/user/workspace"
        );

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
                'rejection_reason' => $data['rejection_reason'],
                'notes' => $data['rejection_reason'], // keeping notes for safety
                'approved_at' => now()
            ]);

            // If linked to a ticket, update the ticket status as well
            if ($purchase->ticket_id) {
                $rejectedTicketStatusId = Status::whereRaw('LOWER(Status_Name) IN ("rejected", "declined")')->value('id');
                $purchase->ticket->update([
                    'Status_ID' => $rejectedTicketStatusId ?? $purchase->ticket->Status_ID,
                    'rejection_reason' => $data['rejection_reason'],
                    'Communication_log' => trim(($purchase->ticket->Communication_log ? $purchase->ticket->Communication_log . "\n" : '') . now()->format('Y-m-d H:i:s') . " - Linked Purchase Request Rejected. Reason: " . $data['rejection_reason']),
                ]);
            }

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
        $recipients = User::whereIn('id', array_filter([$purchase->requester_id, $purchase->requested_by_id]))->get();
        $this->sendDetailedEmail(
            $recipients,
            "Purchase Request Rejected",
            "Acquisition Declined",
            "The purchase request for '{$purchase->item_name}' was unfortunately rejected.",
            [
                "Item" => $purchase->item_name,
                "Rejection Reason" => $data['rejection_reason'],
                "Processed By" => Auth::user()->name,
            ],
            "View My Requests",
            config('app.url') . "/dashboard/user/workspace"
        );

        return response()->json(['message' => 'Purchase request rejected.']);
    }
}
