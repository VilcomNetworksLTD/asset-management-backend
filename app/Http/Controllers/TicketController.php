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
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
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
        $tickets = Ticket::with(['issue.asset.category', 'status'])
            ->where('Employee_ID', $user->id)
            ->latest()
            ->get();

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => 'nullable|integer|exists:assets,id',
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
        if (empty($data['asset_id'])) {
            if (!empty($data['requested_category'])) {
                $description = 'Request Category: ' . $data['requested_category']
                    . "\nRequest Details: " . $data['description'];
            } else {
                $description = 'Subject: ' . ($data['subject'] ?? 'General IT Support')
                    . "\nDetails: " . $data['description'];
            }
        } else {
            $description = $data['description'];
        }

        $pendingStatus = Status::firstOf(['Pending', 'New', 'Open']) ?? 1;
        $recent = Ticket::where('Employee_ID', $user->id)
            ->where('Description', $description)
            ->where('Status_ID', $pendingStatus)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->first();
        if ($recent) {
            return response()->json($recent->load(['user','issue.asset.category','status']), 200);
        }

        $ticket = DB::transaction(function () use ($data, $user) {
            if (empty($data['asset_id'])) {
                $description = '';
                if (!empty($data['requested_category'])) {
                    $description = 'Request Category: ' . $data['requested_category']
                        . "\nRequest Details: " . $data['description'];
                } else {
                    $description = 'Subject: ' . ($data['subject'] ?? 'General IT Support')
                        . "\nDetails: " . $data['description'];
                }

                return Ticket::create([
                    'Employee_ID' => $user->id,
                    'Status_ID' => Status::firstOf(['Pending', 'New', 'Open']) ?? 1,
                    'Priority' => $data['priority'] ?? 'medium',
                    'Description' => $description,
                ]);
            }

            $issue = Issue::create([
                'Employee_ID' => $user->id,
                'Asset_ID' => $data['asset_id'],
                'Issue_Description' => $data['description'],
                'Status_ID' => Status::firstOf(['Pending', 'New', 'Open']) ?? 1,
            ]);

            $ticket = Ticket::create([
                'Employee_ID' => $user->id,
                'Status_ID' => Status::firstOf(['Pending', 'New', 'Open']) ?? 1,
                'Priority' => $data['priority'] ?? 'medium',
                'Description' => $data['description'],
            ]);

            $issue->update(['Ticket_ID' => $ticket->id]);

            return $ticket;
        });

        $admins = User::where('role', 'admin')->get()->filter(fn ($u) => $u->email);
        if ($admins->isNotEmpty()) {
            $subject = "New Support Ticket #{$ticket->id} from {$user->name}";
            $details = "A new support ticket has been created by {$user->name}.\n\n"
                . "Subject: " . ($data['subject'] ?? 'Asset Issue') . "\n"
                . "Details: {$ticket->Description}";

            foreach ($admins as $admin) {
                Mail::raw($details, function ($message) use ($admin, $subject) {
                    $message->to($admin->email)->subject($subject);
                });
            }
        }

        return response()->json($ticket->load(['user', 'issue.asset.category', 'status']), 201);
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
                $ticket->Status_ID = Status::firstOf(['Resolved', 'Closed', 'Completed']) ?? $ticket->Status_ID;
            } elseif ($data['action'] === 'reopen') {
                $ticket->Status_ID = Status::firstOf(['Pending', 'Open', 'New']) ?? $ticket->Status_ID;
            }
        }

        $ticket->save();
        $ticket->load(['user', 'issue.asset.category', 'status']);

        $ticketUser = $ticket->user;
        if ($ticketUser && $ticketUser->email) {
            $subject = "Update on your Support Ticket #{$ticket->id}";
            $details = "There has been an update on your support ticket #{$ticket->id}.\n\n";

            if (!empty($data['communication'])) {
                $details .= "An admin has left a note: '{$data['communication']}'\n\n";
            }
            if (!empty($data['action'])) {
                $ticket->load('status');
                $details .= "The ticket has been " . ($data['action'] === 'resolve' ? 'resolved' : 'reopened')
                    . ", status now: {$ticket->status->Status_Name}.\n\n";
            }
            $details .= "Please log in to view the details.";

            Mail::raw($details, function ($message) use ($ticketUser, $subject) {
                $message->to($ticketUser->email)->subject($subject);
            });
        }

        return response()->json(['message' => 'Ticket updated successfully', 'ticket' => $ticket]);
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
                'category' => $a->category?->Category_Name ?? 'Uncategorized',
                'status' => $a->status,
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
            $storeStatusId = Status::whereIn('Status_Name', ['Ready to Deploy', 'Available'])->value('id');
            $maintStatusId = Status::whereIn('Status_Name', ['Out for Repair', 'Maintenance'])->value('id');
            $closedStatusId = Status::whereIn('Status_Name', ['Resolved', 'Closed'])->value('id');

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
                'details' => "Disposition: {$data['disposition']}. Ticket #{$ticket->id}",
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

            $awaitingStatus = Status::where('Status_Name', 'Awaiting Purchase')->first();
            $ticket->update([
                'Status_ID' => $awaitingStatus->id ?? $ticket->Status_ID,
                'Communication_log' => trim($ticket->Communication_log . "\n" . now()->format('Y-m-d H:i:s') . " - Escalated to Management.")
            ]);

            return response()->json(['message' => 'Escalated successfully.', 'purchase_request' => $purchaseRequest]);
        });
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
            $deployedStatusId = Status::whereIn('Status_Name', ['Deployed', 'Assigned', 'In Use'])->value('id');
            $resolvedStatusId = Status::whereIn('Status_Name', ['Resolved', 'Closed', 'Completed'])->value('id');

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
                'Communication_log' => trim($ticket->Communication_log . "\n" . ($data['communication'] ?? 'Asset assigned.'))
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
            'Status_ID' => Status::firstOf(['Pending','New','Open']) ?? 1,
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