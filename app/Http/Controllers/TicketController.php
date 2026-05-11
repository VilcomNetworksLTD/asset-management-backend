<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\User;
use App\Models\Issue;
use App\Models\Maintenance;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\PurchaseRequest;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Traits\SendsDetailedEmails;

class TicketController extends Controller
{
    use SendsDetailedEmails;

    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    public function index(): JsonResponse
    {
        $tickets = Ticket::with(['user', 'issue.asset.category', 'status'])
            ->latest()
            ->get();

        return response()->json($tickets);
    }

    public function list(Request $request): JsonResponse
    {
        $query = Ticket::with(['user', 'issue.asset.category', 'status']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('Description', 'like', "%{$search}%")
                    ->orWhere('Priority', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($statusId = $request->integer('status_id')) {
            $query->where('Status_ID', $statusId);
        }

        if ($priority = $request->string('priority')->toString()) {
            $query->where('Priority', $priority);
        }

        $perPage = max(1, min(100, $request->integer('per_page', 10)));

        return response()->json($query->latest()->paginate($perPage));
    }

    public function getUserTickets(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Ticket::with(['issue.asset.category', 'status'])
            ->where('Employee_ID', $user->id);

        if ($search = $request->string('search')->toString()) {
            $query->where('Description', 'like', "%{$search}%");
        }

        if ($priority = $request->string('priority')->toString()) {
            $query->where('Priority', $priority);
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => 'nullable|integer|exists:assets,id',
            'other_asset' => 'nullable|string|max:255',
            'requested_category' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'nullable|string|in:low,medium,high',
        ]);

        if (empty($data['asset_id']) && empty($data['requested_category']) && empty($data['subject'])) {
            throw ValidationException::withMessages([
                'subject' => 'Either an asset, a requested category, or a subject for the support ticket is required.',
            ]);
        }

        $user = $request->user() ?? Auth::user();

        $description = '';
        if (empty($data['asset_id']) || !empty($data['other_asset'])) {
            if (!empty($data['other_asset'])) {
                $description = "Asset (Other): {$data['other_asset']}\nDetails: {$data['description']}";
            } elseif (!empty($data['requested_category'])) {
                $description = 'Request Category: ' . $data['requested_category']
                    . "\nRequest Details: " . $data['description'];
            } else {
                $description = 'Subject: ' . ($data['subject'] ?? 'General IT Support')
                    . "\nDetails: " . $data['description'];
            }
        } else {
            $description = $data['description'];
        }

        $pendingStatus = Status::whereRaw('LOWER(Status_Name) IN ("pending", "new", "open")')->value('id') ?? 1;
        $recent = Ticket::where('Employee_ID', $user->id)
            ->where('Description', $description)
            ->where('Status_ID', $pendingStatus)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->first();
        if ($recent) {
            return response()->json($recent->load(['user','issue.asset.category','status']), 200);
        }

         $ticket = DB::transaction(function () use ($data, $user) {
             $statusId = Status::whereRaw('LOWER(Status_Name) IN ("pending", "new", "open")')->value('id') ?? 1;
             if (empty($data['asset_id'])) {
                 $description = '';
                 if (!empty($data['other_asset'])) {
                     $description = "Asset (Other): {$data['other_asset']}\nDetails: {$data['description']}";
                 } elseif (!empty($data['requested_category'])) {
                     $description = 'Request Category: ' . $data['requested_category']
                         . "\nRequest Details: " . $data['description'];
                 } else {
                     $description = 'Subject: ' . ($data['subject'] ?? 'General IT Support')
                         . "\nDetails: " . $data['description'];
                 }

                return Ticket::create([
                    'Employee_ID' => $user->id,
                    'Status_ID' => $statusId,
                    'Priority' => $data['priority'] ?? 'medium',
                    'Description' => $description,
                ]);
            }

            $issue = Issue::create([
                'Employee_ID' => $user->id,
                'Asset_ID' => $data['asset_id'],
                'Issue_Description' => $data['description'],
                'Status_ID' => $statusId,
            ]);

            $ticket = Ticket::create([
                'Employee_ID' => $user->id,
                'Status_ID' => $statusId,
                'Priority' => $data['priority'] ?? 'medium',
                'Description' => $data['description'],
            ]);

            $issue->update(['Ticket_ID' => $ticket->id]);

            return $ticket;
        });

        $admins = User::where('role', 'admin')->get()->filter(fn ($u) => $u->email);
        if ($admins->isNotEmpty()) {
            $this->sendDetailedEmail(
                $admins,
                "New Support Ticket Received",
                "Action Required: New Ticket",
                "A new support ticket has been created by {$user->name} and is awaiting administrative attention.",
                [
                    "Requester" => $user->name,
                    "Department" => $user->department?->name ?? 'N/A',
                    "Priority" => ucfirst($data['priority'] ?? 'medium'),
                    "Description" => $data['description'],
                ],
                "View Tickets",
                config('app.url') . "/dashboard/support/tickets"
            );
        }

        // Notify the user too
        $this->sendDetailedEmail(
            $user,
            "Ticket Created Successfully",
            "Support Ticket Logged",
            "Your support ticket has been successfully logged. Our IT team will review it shortly.",
            [
                "Subject" => $data['subject'] ?? 'Asset Issue',
                "Priority" => ucfirst($data['priority'] ?? 'medium'),
                "Status" => "Pending Review",
            ],
            "Track My Ticket",
            config('app.url') . "/dashboard/user/workspace"
        );

        return response()->json(['success' => true, 'message' => 'Ticket created successfully.'], 201);
    }

    public function resolveTicket(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'communication' => 'nullable|string|max:2000',
        ]);

        $ticket = Ticket::findOrFail($id);
        $resolvedStatusId = Status::whereRaw('LOWER(Status_Name) IN ("closed", "resolved", "completed")')->value('id');

        $ticket->update([
            'Status_ID' => $resolvedStatusId ?? $ticket->Status_ID,
            'Communication_log' => trim(($ticket->Communication_log ? $ticket->Communication_log . "\n" : '') . now()->format('Y-m-d H:i:s') . " - Ticket closed. " . ($data['communication'] ?? 'No additional comments.')),
        ]);

        ActivityLog::create([
            'asset_id' => $ticket->issue?->asset?->id,
            'Employee_ID' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Ticket Resolved',
            'target_type' => 'Ticket',
            'target_name' => '#' . $ticket->id,
            'details' => $data['communication'] ?? 'Ticket marked as resolved',
        ]);

        $ticket->load(['user', 'issue.asset.category', 'status']);
        return response()->json(['message' => 'Ticket resolved successfully.', 'ticket' => $ticket]);
    }

    public function rejectTicket(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'rejection_reason' => 'required|string|max:2000',
        ]);

        $ticket = Ticket::findOrFail($id);
        $rejectedStatusId = Status::whereRaw('LOWER(Status_Name) IN ("rejected", "declined")')->value('id');

        $ticket->update([
            'Status_ID' => $rejectedStatusId ?? $ticket->Status_ID,
            'rejection_reason' => $data['rejection_reason'],
            'Communication_log' => trim(($ticket->Communication_log ? $ticket->Communication_log . "\n" : '') . now()->format('Y-m-d H:i:s') . " - Ticket rejected. Reason: " . $data['rejection_reason']),
        ]);

        ActivityLog::create([
            'asset_id' => $ticket->issue?->asset?->id,
            'Employee_ID' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'System',
            'action' => 'Ticket Rejected',
            'target_type' => 'Ticket',
            'target_name' => '#' . $ticket->id,
            'details' => $data['rejection_reason'],
        ]);

        $ticket->load(['user', 'issue.asset.category', 'status']);
        return response()->json(['message' => 'Ticket rejected.', 'ticket' => $ticket]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'description' => 'nullable|string|max:2000',
            'priority' => 'nullable|string|in:low,medium,high',
            'communication' => 'nullable|string|max:2000',
            'action' => 'nullable|string|in:resolve,reopen',
        ]);

        $ticket = Ticket::findOrFail($id);

        if (array_key_exists('description', $data)) {
            $ticket->Description = $data['description'];
        }
        if (array_key_exists('priority', $data)) {
            $ticket->Priority = $data['priority'];
        }

        if (!empty($data['communication'])) {
            $newLog = now()->format('Y-m-d H:i:s') . ' - Note: ' . $data['communication'];
            $ticket->Communication_log = trim(($ticket->Communication_log ? $ticket->Communication_log . "\n" : '') . $newLog);
        }

        if (!empty($data['action'])) {
            if ($data['action'] === 'resolve') {
                $ticket->Status_ID = Status::whereRaw('LOWER(Status_Name) IN ("closed", "resolved", "completed")')->value('id') ?? $ticket->Status_ID;
            } elseif ($data['action'] === 'reopen') {
                $ticket->Status_ID = Status::whereRaw('LOWER(Status_Name) IN ("pending", "open", "new")')->value('id') ?? $ticket->Status_ID;
            }
        }

        $ticket->save();
        $ticket->load(['user', 'issue.asset.category', 'status']);

        $ticketUser = $ticket->user;
        if ($ticketUser) {
            $statusName = $ticket->status?->Status_Name ?? 'Updated';
            $this->sendDetailedEmail(
                $ticketUser,
                "Ticket Update: " . $statusName,
                "Support Ticket Progress",
                "There has been an update on your support ticket.",
                [
                    "Current Status" => $statusName,
                    "Admin Note" => $data['communication'] ?? 'Status updated',
                    "Last Updated" => now()->toDayDateTimeString(),
                ],
                "View Progress",
                config('app.url') . "/dashboard/user/workspace"
            );
        }

        return response()->json(['success' => true, 'message' => 'Ticket updated successfully']);
    }

    public function destroy(int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted successfully']);
    }

    // --- NEW ARCHITECTURE FUNCTIONS ADDED BELOW ---

    /**
     * FIX for frontend error: Fetches assets assigned to the logged-in user for return selection.
     */
    public function getMyReturnableAssets(Request $request): JsonResponse
    {
        $user = $request->user() ?? Auth::user();

        $assets = Asset::query()
            ->with(['status', 'category'])
            ->where('Employee_ID', $user->id)
            ->latest()
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'asset_tag' => 'AST-' . str_pad((string) $a->id, 4, '0', STR_PAD_LEFT),
                'model' => $a->Asset_Name,
                'serial' => $a->Serial_No,
                'category' => $a->category?->name ?? 'Uncategorized',
                'status_name' => $a->status?->Status_Name ?? 'Unknown',
            ]);

        return response()->json($assets);
    }

    /**
     * Fetches specialized queues for Admin Workflow Dashboard.
     */
    public function getWorkflowQueues(Request $request): JsonResponse
    {
        $user = $request->user() ?? Auth::user();

        if (($user->role ?? 'user') !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $equipmentRequests = Ticket::with(['user', 'issue.asset.category', 'status'])
            ->where('Description', 'like', 'Request Category:%')
            ->latest()
            ->get();

        $returnRequests = Ticket::with(['user', 'issue.asset.category', 'status'])
            ->where('Description', 'like', 'Workflow Type: RETURN%')
            ->latest()
            ->get();

        return response()->json([
            'equipment_requests' => $equipmentRequests,
            'return_requests' => $returnRequests,
        ]);
    }

    /**
     * Processes a return request by deciding if asset goes to storage or maintenance.
     */
    public function processReturn(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'disposition' => 'required|in:store,maintenance',
            'notes' => 'nullable|string|max:2000',
            'maintenance_type' => 'nullable|string|max:255',
        ]);

        $ticket = Ticket::findOrFail($id);
        $assetId = $this->extractAssetIdFromDescription($ticket->Description ?? '');

        if (!$assetId) {
            return response()->json(['message' => 'Asset ID not found in ticket description.'], 422);
        }

        $asset = Asset::findOrFail($assetId);

        DB::transaction(function () use ($ticket, $asset, $data) {
            $storeStatusId = Status::whereRaw('LOWER(Status_Name) IN ("ready to deploy", "available")')->value('id');
            $maintStatusId = Status::whereRaw('LOWER(Status_Name) IN ("out for repair", "maintenance")')->value('id');
            $closedStatusId = Status::whereRaw('LOWER(Status_Name) IN ("closed", "resolved")')->value('id');

            if ($data['disposition'] === 'store') {
                $asset->update([
                    'Employee_ID' => null,
                    'Status_ID' => $storeStatusId ?? $asset->Status_ID,
                ]);
            } else {
                $asset->update([
                    'Employee_ID' => null,
                    'Status_ID' => $maintStatusId ?? $asset->Status_ID,
                ]);

                Maintenance::create([
                    'Asset_ID' => $asset->id,
                    'Ticket_ID' => $ticket->id,
                    'Maintenance_Type' => $data['maintenance_type'] ?? 'Inspection after return',
                    'Description' => $data['notes'] ?? 'Created from return workflow.',
                    'Status_ID' => $maintStatusId ?? 1,
                    'Maintenance_Date' => now(),
                ]);
            }

            $ticket->update([
                'Status_ID' => $closedStatusId ?? $ticket->Status_ID,
                'Communication_log' => trim($ticket->Communication_log . "\nReturn processed. Disposition: " . $data['disposition']),
            ]);

            ActivityLog::create([
                'asset_id' => $asset->id,
                'Employee_ID' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'Admin',
                'action' => 'Processed Return',
                'target_type' => 'Asset',
                'target_name' => $asset->Asset_Name,
                'details' => "Disposition: {$data['disposition']}",
            ]);
        });

        return response()->json(['message' => 'Return processed successfully.']);
    }

    private function extractAssetIdFromDescription(string $description): ?int
    {
        if (preg_match('/Asset ID:\s*(\d+)/i', $description, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    // --- REMAINDER OF YOUR WORKING FUNCTIONS ---

    public function escalateToPurchase(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $data = $request->validate([
            'item_name' => 'required|string|max:255',
            'estimated_cost' => 'nullable|numeric',
            'reason' => 'required|string',
        ]);

        return DB::transaction(function () use ($ticket, $data) {
            $purchaseRequest = \App\Models\PurchaseRequest::create([
                'user_id' => $ticket->Employee_ID,
                'type' => 'asset_request',
                'ticket_id' => $ticket->id,
                'item_name' => $data['item_name'],
                'estimated_cost' => $data['estimated_cost'],
                'description' => $data['reason'],
                'status' => 'pending'
            ]);

            $awaitingStatus = Status::whereRaw('LOWER(Status_Name) IN ("escalated to management", "escalated", "awaiting purchase")')->value('id');
            $ticket->update([
                'Status_ID' => $awaitingStatus ?? $ticket->Status_ID,
                'Communication_log' => trim($ticket->Communication_log . "\n" . now()->format('Y-m-d H:i:s') . " - Escalated to Management.")
            ]);

            return response()->json(['message' => 'Escalated successfully.', 'purchase_request' => $purchaseRequest]);
        });
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $ticket = Ticket::with('user')->findOrFail($id);

        DB::transaction(function () use ($ticket, $data) {
            $rejectedStatusId = Status::whereIn('Status_Name', ['Rejected', 'Cancelled', 'Closed'])->value('id');

            $ticket->update([
                'Status_ID' => $rejectedStatusId ?? $ticket->Status_ID,
                'Communication_log' => trim($ticket->Communication_log . "\n" . now()->format('Y-m-d H:i:s') . " - REQUEST REJECTED. Reason: " . $data['reason'])
            ]);
        });

        return response()->json([
            'message' => 'Ticket rejected successfully.',
            'ticket' => $ticket->fresh()->load(['user', 'issue.asset.category', 'status']),
        ]);
    }

    public function assignAsset(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => 'required|integer|exists:assets,id',
            'communication' => 'nullable|string|max:2000',
            'accessory_allocations' => 'nullable|array',
            'accessory_allocations.*.id' => 'required|integer|exists:accessories,id',
            'accessory_allocations.*.qty' => 'required|integer|min:1',
            'consumable_allocations' => 'nullable|array',
            'consumable_allocations.*.id' => 'required|integer|exists:consumables,id',
            'consumable_allocations.*.qty' => 'required|integer|min:1',
        ]);

        $ticket = Ticket::with('user')->findOrFail($id);
        $asset = Asset::findOrFail($data['asset_id']);

        $bundleSummary = DB::transaction(function () use ($ticket, $asset, $data) {
            $deployedStatusId = Status::where('Status_Name', 'Deployed')->value('id') ?? Status::whereRaw('LOWER(Status_Name) IN ("deployed", "assigned", "in use")')->value('id');
            $resolvedStatusId = Status::where('Status_Name', 'Closed')->value('id') ?? Status::whereRaw('LOWER(Status_Name) IN ("closed", "resolved", "completed")')->value('id');

            $asset->update([
                'Employee_ID' => $ticket->Employee_ID,
                'Status_ID' => $deployedStatusId ?? $asset->Status_ID,
            ]);

            $bundleItems = [];
            foreach (($data['accessory_allocations'] ?? []) as $item) {
                $accessory = Accessory::query()->lockForUpdate()->findOrFail($item['id']);
                $accessory->decrement('remaining_qty', (int)$item['qty']);
                $bundleItems[] = "Accessory: {$accessory->name} x{$item['qty']}";
            }

            foreach (($data['consumable_allocations'] ?? []) as $item) {
                $consumable = Consumable::query()->lockForUpdate()->findOrFail($item['id']);
                $consumable->decrement('in_stock', (int)$item['qty']);
                $bundleItems[] = "Consumable: {$consumable->item_name} x{$item['qty']}";
            }

            $ticket->update([
                'Status_ID' => $resolvedStatusId ?? $ticket->Status_ID,
                'Communication_log' => trim($ticket->Communication_log . "\n" . ($data['communication'] ?? 'Ticket closed and asset assigned.'))
            ]);

            if ($ticket->issue) {
                Issue::where('id', $ticket->issue->id)->update(['Asset_ID' => $asset->id, 'Status_ID' => $resolvedStatusId ?? 1]);
            }

            return $bundleItems;
        });

        return response()->json([
            'message' => 'Asset assigned successfully.',
            'ticket' => $ticket->fresh()->load(['user', 'issue.asset.category', 'status']),
            'asset' => $asset->fresh()->load(['status', 'category']),
        ]);
    }

    public function createReturnRequest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => 'nullable|integer|exists:assets,id',
            'items' => 'nullable|array',
            'condition' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:2000',
        ]);

        $user = $request->user() ?? Auth::user();
        $asset = Asset::findOrFail($data['asset_id']);

        $description = "Workflow Type: RETURN\nAsset ID: {$asset->id}\nAsset Name: {$asset->Asset_Name}\nCondition: " . ($data['condition'] ?? 'Not specified');

        $ticket = Ticket::create([
            'Employee_ID' => $user->id,
            'Status_ID' => Status::whereRaw('LOWER(Status_Name) IN ("pending", "new", "open")')->value('id') ?? 1,
            'Priority' => 'medium',
            'Description' => $description,
        ]);

        return response()->json(['message' => 'Return request submitted.', 'ticket' => $ticket], 201);
    }

    protected function getStatusId(string $statusName): ?int
    {
        return Status::where('Status_Name', $statusName)->value('id');
    }
}