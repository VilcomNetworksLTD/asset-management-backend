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
use App\Mail\PurchaseEscalation;
use App\Mail\PurchaseDecision;

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
        foreach ($managers as $manager) {
            if ($manager->email) {
                Mail::to($manager->email)->send(new PurchaseEscalation($purchaseRequest, $manager));
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

        $maintenance = Maintenance::with(['asset', 'status'])->findOrFail($data['maintenance_id']);

        $hasExistingPurchase = PurchaseRequest::where('maintenance_id', $maintenance->id)->exists();
        $maintStatus = strtolower($maintenance->status?->Status_Name ?? '');
        $maintWorkflow = strtolower($maintenance->Workflow_Status ?? '');
        
        $alreadyEscalated = $hasExistingPurchase 
            || $maintWorkflow === 'escalated' 
            || $maintWorkflow === 'on hold'
            || $maintStatus === 'escalated' 
            || $maintStatus === 'on hold';

        if ($alreadyEscalated) {
            return response()->json(['message' => 'This maintenance record has already been escalated.'], 400);
        }

        $isTerminal = in_array($maintStatus, ['solved', 'completed', 'closed', 'resolved', 'cancelled', 'archived', 'rejected', 'declined'])
            || in_array($maintWorkflow, ['solved', 'completed', 'closed', 'resolved', 'cancelled', 'archived', 'rejected', 'declined']);

        if ($isTerminal) {
            return response()->json(['message' => 'This maintenance record has already been resolved or completed.'], 400);
        }

        if ($maintenance->Ticket_ID) {
            $ticket = \App\Models\Ticket::find($maintenance->Ticket_ID);
            if ($ticket) {
                $ticketStatusName = strtolower($ticket->status?->Status_Name ?? '');
                $ticketEscalated = str_contains($ticketStatusName, 'escalat') 
                    || str_contains($ticketStatusName, 'awaiting')
                    || $ticketStatusName === 'approved';
                if ($ticketEscalated) {
                    return response()->json(['message' => 'The linked ticket has already been escalated or approved.'], 400);
                }
            }
        }

        $purchaseRequest = PurchaseRequest::create([
            'maintenance_id' => $maintenance->id,
            'user_id' => $maintenance->asset && $maintenance->asset->Employee_ID ? $maintenance->asset->Employee_ID : Auth::id(),
            'type' => 'maintenance_part',
            'item_name' => $data['item_name'],
            'description' => $data['reason'],
            'estimated_cost' => $data['estimated_cost'],
            'status' => 'pending',
        ]);

        // ✅ Transition maintenance to "On Hold" (awaiting purchase/management decision).
        // This triggers MaintenanceObserver → updates asset inventory to "Out for Repair" → writes audit log.
        $maintenance->transitionTo(Maintenance::WORKFLOW_ON_HOLD, Auth::user());

        // Write a specific audit log entry for this escalation action
        ActivityLog::create([
            'asset_id'    => $maintenance->asset?->id,
            'Employee_ID' => Auth::id(),
            'user_name'   => Auth::user()->name ?? 'System',
            'action'      => 'Maintenance Escalated to Purchase',
            'target_type' => 'Maintenance',
            'target_name' => $maintenance->Maintenance_Type ?? 'Routine Maintenance',
            'details'     => "Maintenance #{$maintenance->id} escalated: purchase request created for '{$data['item_name']}'. Awaiting management approval.",
        ]);

        // Notify Management
        $managers = User::where('role', 'management')->get();
        foreach ($managers as $manager) {
            if ($manager->email) {
                Mail::to($manager->email)->send(new PurchaseEscalation($purchaseRequest, $manager));
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
            Mail::to($requestUser->email)->send(new PurchaseDecision($pRequest, $requestUser, $data['status']));
        }

        // Notify admins
        foreach ($admins as $admin) {
            if ($admin->email) {
                Mail::to($admin->email)->send(new PurchaseDecision($pRequest, $admin, $data['status']));
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

        \Illuminate\Support\Facades\DB::transaction(function () use ($pRequest) {
            $pRequest->update(['status' => 'purchased']);

            // Update linked ticket status to Solved if applicable
            if ($pRequest->ticket_id) {
                $ticket = \App\Models\Ticket::find($pRequest->ticket_id);
                if ($ticket) {
                    $solvedStatusId = \App\Models\Status::where('Status_Name', 'Solved')->value('id')
                        ?? \App\Models\Status::firstOrCreate(['Status_Name' => 'Solved'])->id;

                    $ticket->update([
                        'Status_ID' => $solvedStatusId,
                        'Communication_log' => trim(($ticket->Communication_log ? $ticket->Communication_log . "\n" : '') . now()->format('Y-m-d H:i:s') . " - Item purchased. Support ticket solved.")
                    ]);

                    if ($ticket->issue) {
                        $ticket->issue->update(['Status_ID' => $solvedStatusId]);
                    }
                }
            }
        });

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



        // Email management
        $managers = User::where('role', 'management')->get();
        foreach ($managers as $manager) {
            if ($manager->email) {
                Mail::to($manager->email)->send(new PurchaseEscalation($purchase, $manager));
            }
        }

        // Email the requester that their request has been escalated
        if ($purchase->requester && $purchase->requester->email) {
            Mail::to($purchase->requester->email)->send(new PurchaseDecision($purchase, $purchase->requester, 'escalated'));
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
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($purchase, $user) {
            $purchase->update([
                'status' => 'approved',
                'management_id' => $user->id,
                'approved_at' => now()
            ]);

            // Update linked ticket status if applicable
            if ($purchase->ticket_id) {
                $ticket = \App\Models\Ticket::find($purchase->ticket_id);
                if ($ticket) {
                    $approvedStatusId = \App\Models\Status::where('Status_Name', 'Approved')->value('id')
                        ?? \App\Models\Status::firstOrCreate(['Status_Name' => 'Approved'])->id;

                    $ticket->update([
                        'Status_ID' => $approvedStatusId,
                        'Communication_log' => trim(($ticket->Communication_log ? $ticket->Communication_log . "\n" : '') . now()->format('Y-m-d H:i:s') . " - Approved by management.")
                    ]);
                }
            }


        });

        // Notify Staff and Admin
        $recipients = User::whereIn('email', array_filter([$purchase->requester?->email]))->get();
        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new PurchaseDecision($purchase, $recipient, 'approved'));
        }

        return response()->json(['message' => 'Purchase request approved.', 'purchase' => $purchase->fresh()]);
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

        \Illuminate\Support\Facades\DB::transaction(function () use ($purchase, $user, $data) {
            $purchase->update([
                'status' => 'rejected',
                'management_id' => $user->id,
                'rejection_reason' => $data['rejection_reason'],
                'approved_at' => now()
            ]);

            // Update linked ticket status if applicable
            if ($purchase->ticket_id) {
                $ticket = \App\Models\Ticket::find($purchase->ticket_id);
                if ($ticket) {
                    $rejectedStatusId = \App\Models\Status::whereRaw('LOWER(Status_Name) IN ("rejected", "declined", "cancelled")')->value('id')
                        ?? \App\Models\Status::firstOrCreate(['Status_Name' => 'Rejected'])->id;

                    $ticket->update([
                        'Status_ID' => $rejectedStatusId,
                        'rejection_reason' => $data['rejection_reason'],
                        'Communication_log' => trim(($ticket->Communication_log ? $ticket->Communication_log . "\n" : '') . now()->format('Y-m-d H:i:s') . " - Rejected by management. Reason: " . $data['rejection_reason'])
                    ]);
                }
            }


        });

        // Notify Staff and Admin
        $recipients = User::whereIn('email', array_filter([$purchase->requester?->email]))->get();
        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new PurchaseDecision($purchase, $recipient, 'rejected'));
        }

        return response()->json(['message' => 'Purchase request rejected.']);
    }
}
