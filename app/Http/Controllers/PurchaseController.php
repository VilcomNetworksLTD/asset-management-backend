<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\Maintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\ActivityLog;

class PurchaseController extends Controller
{
    /**
     * List purchase requests based on role
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = PurchaseRequest::with(['requester:id,name', 'management:id,name', 'ticket:id', 'maintenance:id']);

        if ($user->role === 'staff' || $user->role === 'hod') {
            $query->where('user_id', $user->id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Management and Admin see all
        return response()->json($query->latest()->get());
    }

    /**
     * Create a new purchase request
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_name' => 'required|string|max:255',
            'type' => 'required|string|in:asset_request,maintenance_part',
            'description' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric',
        ]);

        $purchaseRequest = PurchaseRequest::create([
            'user_id' => Auth::id(),
            'item_name' => $data['item_name'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'estimated_cost' => $data['estimated_cost'] ?? null,
            'status' => 'pending',
        ]);

        // Notify Management about new purchase request (not just admins)
        $managers = User::where('role', 'management')->get();
        foreach ($managers as $manager) {
            if ($manager->email) {
                Mail::raw(
                    "A new purchase request has been submitted and requires your review.\n\n" .
                    "Item: {$purchaseRequest->item_name}\n" .
                    "Type: {$purchaseRequest->type}\n" .
                    "Requested by: " . Auth::user()->name . "\n" .
                    "Estimated Cost: " . ($data['estimated_cost'] ?? 'Not specified') . "\n\n" .
                    "Please review this request in the Purchase Approvals section.",
                    function ($m) use ($manager, $purchaseRequest) {
                        $m->to($manager->email)
                          ->subject("New Purchase Request: {$purchaseRequest->item_name}");
                    }
                );
            }
        }
        
        return response()->json([
            'message' => 'Purchase request submitted for management review.',
            'request' => $purchaseRequest
        ], 201);
    }

    /**
     * Escalate from maintenance to purchase request
     */
    public function escalateFromMaintenance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'maintenance_id' => 'required|exists:maintenance,id',
            'item_name' => 'required|string|max:255',
            'estimated_cost' => 'nullable|numeric',
            'reason' => 'required|string',
        ]);

        $maintenance = Maintenance::with('asset')->findOrFail($data['maintenance_id']);

        $purchaseRequest = PurchaseRequest::create([
            'maintenance_id' => $maintenance->id,
            'user_id' => $maintenance->asset && $maintenance->asset->Employee_ID ? $maintenance->asset->Employee_ID : Auth::id(),
            'type' => 'maintenance_part',
            'item_name' => $data['item_name'],
            'description' => $data['reason'],
            'estimated_cost' => $data['estimated_cost'],
            'status' => 'pending',
        ]);

        // Notify Management
        $managers = User::where('role', 'management')->get();
        foreach ($managers as $manager) {
            if ($manager->email) {
                Mail::raw("A new purchase request for '{$purchaseRequest->item_name}' (Maintenance ID: {$maintenance->id}) requires your approval.", function ($m) use ($manager) {
                    $m->to($manager->email)->subject("Action Required: Maintenance Purchase Request");
                });
            }
        }

        return response()->json($purchaseRequest, 201);
    }

    /**
     * Management: Approve or Reject
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if ($user->role !== 'management' && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized Access Protocol.'], 403);
        }

        $pRequest = PurchaseRequest::findOrFail($id);
        
        $data = $request->validate([
            'status' => 'required|string|in:approved,rejected',
            'rejection_reason' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric',
        ]);

        $pRequest->update([
            'status' => $data['status'],
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'management_id' => $user->id,
            'estimated_cost' => $data['estimated_cost'] ?? $pRequest->estimated_cost,
        ]);

        // Send email notification to requester and admin
        $requestUser = User::find($pRequest->user_id);
        $admins = User::where('role', 'admin')->get();

        $subject = $data['status'] === 'approved' 
            ? "Purchase Request Approved: {$pRequest->item_name}"
            : "Purchase Request Rejected: {$pRequest->item_name}";
        
        $message = $data['status'] === 'approved'
            ? "Your purchase request for '{$pRequest->item_name}' has been APPROVED by Management.\n\n" .
              "Approved Budget: " . ($data['estimated_cost'] ?? $pRequest->estimated_cost ?? 'Not specified') . "\n\n" .
              "Procurement can now proceed."
            : "Your purchase request for '{$pRequest->item_name}' has been REJECTED by Management.\n\n" .
              "Reason: {$data['rejection_reason']}";

        // Notify requester
        if ($requestUser && $requestUser->email) {
            Mail::raw($message, function ($m) use ($requestUser, $subject) {
                $m->to($requestUser->email)->subject($subject);
            });
        }

        // Notify admins
        $requesterEmail = $requestUser ? $requestUser->email : null;
        foreach ($admins as $admin) {
            if ($admin->email && $requesterEmail && $admin->email !== $requesterEmail) {
                Mail::raw(
                    "Purchase request status update: {$pRequest->item_name}\n\n" .
                    "Status: " . strtoupper($data['status']) . "\n" .
                    "By: Management\n" .
                    "Requester has been notified.",
                    function ($m) use ($admin, $subject) {
                        $m->to($admin->email)->subject($subject);
                    }
                );
            }
        }
        
        return response()->json([
            'message' => "Request " . ucfirst($data['status']) . " successfully.",
            'request' => $pRequest
        ]);
    }

    /**
     * Mark as Purchased
     */
    public function markAsPurchased(int $id): JsonResponse
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'management') {
           return response()->json(['message' => 'Administrative privilege required.'], 403);
        }

        $pRequest = PurchaseRequest::findOrFail($id);
        $pRequest->update(['status' => 'purchased']);

        return response()->json(['message' => 'Item marked as acquired/purchased.']);
    }

    /**
     * Admin: Get all purchase requests for admin review
     */
    public function adminIndex(): JsonResponse
    {
        $requests = PurchaseRequest::with(['requester', 'management', 'ticket', 'maintenance.asset'])
            ->latest()
            ->get();
        return response()->json($requests);
    }

    /**
     * Admin: Escalate a purchase request to management
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
                    "Please review and approve or reject this request in the Purchase Approvals section.",
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

        return response()->json(['message' => 'Request escalated to management.', 'request' => $purchase]);
    }

    /**
     * Management: Approve purchase request
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if ($user->role !== 'management' && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized Access Protocol.'], 403);
        }

        $purchase = PurchaseRequest::with(['requester'])->findOrFail($id);
        
        $purchase->update([
            'status' => 'approved',
            'management_id' => $user->id,
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

        // Notify Staff and Admin
        $recipients = array_filter([$purchase->requester?->email]);
        foreach ($recipients as $email) {
            Mail::raw("The purchase request for '{$purchase->item_name}' has been APPROVED by management. Procurement can now proceed.", function ($m) use ($email, $purchase) {
                $m->to($email)->subject("Purchase Request Approved: {$purchase->item_name}");
            });
        }

        return response()->json(['message' => 'Purchase request approved.', 'purchase' => $purchase]);
    }

    /**
     * Management: Reject purchase request
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if ($user->role !== 'management' && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized Access Protocol.'], 403);
        }

        $purchase = PurchaseRequest::with(['requester'])->findOrFail($id);
        $data = $request->validate(['rejection_reason' => 'required|string']);

        $purchase->update([
            'status' => 'rejected',
            'management_id' => $user->id,
            'rejection_reason' => $data['rejection_reason'],
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

        // Notify Staff and Admin
        $recipients = array_filter([$purchase->requester?->email]);
        foreach ($recipients as $email) {
            Mail::raw("The purchase request for '{$purchase->item_name}' was rejected. Reason: {$data['rejection_reason']}", function ($m) use ($email, $purchase) {
                $m->to($email)->subject("Purchase Request Rejected: {$purchase->item_name}");
            });
        }

        return response()->json(['message' => 'Purchase request rejected.']);
    }
}
