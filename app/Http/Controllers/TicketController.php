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
        $tickets = Ticket::with(['user', 'issue.asset', 'status'])
            ->latest()
            ->get();

        return response()->json($tickets);
    }

    public function list(Request $request): JsonResponse
    {
        $query = Ticket::with(['user', 'issue.asset', 'status']);

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
        $tickets = Ticket::with(['issue.asset', 'status'])
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
            'subject' => 'nullable|string|max:255', // New field for general support
            'description' => 'required|string|max:2000',
            'priority' => 'nullable|string|in:low,medium,high',
        ]);

        if (empty($data['asset_id']) && empty($data['requested_category']) && empty($data['subject'])) {
            throw ValidationException::withMessages([
                'subject' => 'Either an asset, a requested category, or a subject for the support ticket is required.',
            ]);
        }

        $user = $request->user() ?? Auth::user();

        // compute description early so we can detect duplicates
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

        // quick de‑duplication - if the same user created a pending ticket with the
        // same description within the last 30 seconds, just return it. this
        // protects against multiple clicks reproducing the request.
        $pendingStatus = Status::firstOf(['Pending', 'New', 'Open']) ?? 1;
        $recent = Ticket::where('Employee_ID', $user->id)
            ->where('Description', $description)
            ->where('Status_ID', $pendingStatus)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->first();
        if ($recent) {
            return response()->json($recent->load(['user','issue.asset','status']), 200);
        }

        $ticket = DB::transaction(function () use ($data, $user) {
            // Path for tickets not tied to a specific asset
            if (empty($data['asset_id'])) {
                $description = '';
                if (!empty($data['requested_category'])) {
                    // Generic equipment request path (no specific asset selected by staff)
                    $description = 'Request Category: ' . $data['requested_category']
                        . "\nRequest Details: " . $data['description'];
                } else {
                    // General IT support request (e.g., email issues)
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

            $ticketPayload = [
                'Employee_ID' => $user->id,
                'Status_ID' => Status::firstOf(['Pending', 'New', 'Open']) ?? 1,
                'Priority' => $data['priority'] ?? 'medium',
                'Description' => $data['description'],
            ];

            if (Schema::hasColumn('tickets', 'Issue_ID')) {
                $ticketPayload['Issue_ID'] = $issue->id;
            }

            $ticket = Ticket::create($ticketPayload);

            $issue->update(['Ticket_ID' => $ticket->id]);

            return $ticket;
        });

        // Notify admins about the new ticket
        $admins = User::where('role', 'admin')->get()->filter(fn ($u) => $u->email);
        if ($admins->isNotEmpty()) {
            $subject = "New Support Ticket #{$ticket->id} from {$user->name}";
            $details = "A new support ticket has been created by {$user->name}.\n\n"
                . "Subject: " . ($data['subject'] ?? 'Asset Issue') . "\n"
                . "Details: {$ticket->Description}";

            // In a real application, this would use a Mailable class.
            foreach ($admins as $admin) {
                Mail::raw($details, function ($message) use ($admin, $subject) {
                    $message->to($admin->email)->subject($subject);
                });
            }
        }

        return response()->json($ticket->load(['user', 'issue.asset', 'status']), 201);
    }

    public function update(Request $request, $id)
    {
        // status updates are determined by the action; we no longer accept
        // a status_id directly from the client. allowed actions are
        // `resolve` and `reopen` but other verbs may be added later.
        $data = $request->validate([
            'description' => 'nullable|string|max:2000',
            'priority' => 'nullable|string|in:low,medium,high',
            'communication' => 'nullable|string|max:2000',
            'action' => 'nullable|string|in:resolve,reopen',
        ]);

        $ticket = Ticket::findOrFail($id);

        // Update fields if they are present in the request
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

        // determine status automatically based on requested action
        if (!empty($data['action'])) {
            if ($data['action'] === 'resolve') {
                $ticket->Status_ID = Status::firstOf(['Resolved', 'Closed', 'Completed']) ?? $ticket->Status_ID;
            } elseif ($data['action'] === 'reopen') {
                $ticket->Status_ID = Status::firstOf(['Pending', 'Open', 'New']) ?? $ticket->Status_ID;
            }
        }

        $ticket->save();

        // Reload relations for the response
        $ticket->load(['user', 'issue.asset', 'status']);

        // Notify user about the update
        $ticketUser = $ticket->user;
        if ($ticketUser && $ticketUser->email) {
            $subject = "Update on your Support Ticket #{$ticket->id}";
            $details = "There has been an update on your support ticket #{$ticket->id}.\n\n";

            if (!empty($data['communication'])) {
                $details .= "An admin has left a note: '{$data['communication']}'\n\n";
            }
            if (!empty($data['action'])) {
                // reload status relationship to reflect change
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
            $deployedStatusId = Status::query()
                ->whereIn('Status_Name', ['Deployed', 'Assigned', 'In Use'])
                ->value('id');

            $resolvedStatusId = Status::query()
                ->whereIn('Status_Name', ['Resolved', 'Closed', 'Completed'])
                ->value('id');

            $asset->update([
                'Employee_ID' => $ticket->Employee_ID,
                'Status_ID' => $deployedStatusId ?? $asset->Status_ID,
            ]);

            $bundleItems = [];

            foreach (($data['accessory_allocations'] ?? []) as $item) {
                $accessory = Accessory::query()->lockForUpdate()->findOrFail($item['id']);
                $qty = (int) $item['qty'];

                if ((int) $accessory->remaining_qty < $qty) {
                    throw ValidationException::withMessages([
                        'accessory_allocations' => ["Insufficient accessory stock for {$accessory->name}. Requested {$qty}, available {$accessory->remaining_qty}."],
                    ]);
                }

                $accessory->decrement('remaining_qty', $qty);
                $bundleItems[] = "Accessory: {$accessory->name} x{$qty}";
            }

            foreach (($data['consumable_allocations'] ?? []) as $item) {
                $consumable = Consumable::query()->lockForUpdate()->findOrFail($item['id']);
                $qty = (int) $item['qty'];

                if ((int) $consumable->in_stock < $qty) {
                    throw ValidationException::withMessages([
                        'consumable_allocations' => ["Insufficient consumable stock for {$consumable->item_name}. Requested {$qty}, available {$consumable->in_stock}."],
                    ]);
                }

                $consumable->decrement('in_stock', $qty);
                $bundleItems[] = "Consumable: {$consumable->item_name} x{$qty}";
            }

            $bundleLine = empty($bundleItems)
                ? null
                : 'Bundle Items: ' . implode(', ', $bundleItems);

            $ticket->update([
                'Status_ID' => $resolvedStatusId ?? $ticket->Status_ID,
                'Communication_log' => trim(($ticket->Communication_log ? $ticket->Communication_log . "\n" : '')
                    . ($data['communication'] ?? 'Asset assigned by admin')),
            ]);

            if ($bundleLine) {
                $ticket->update([
                    'Communication_log' => trim(($ticket->Communication_log ? $ticket->Communication_log . "\n" : '') . $bundleLine),
                ]);
            }

            if (Schema::hasColumn('tickets', 'Issue_ID') && $ticket->Issue_ID) {
                Issue::where('id', $ticket->Issue_ID)->update([
                    'Asset_ID' => $asset->id,
                    'Status_ID' => $resolvedStatusId ?? 1,
                ]);
            }

            $assignedToUser = $ticket->user;

            ActivityLog::create([
                'asset_id' => $asset->id,
                'Employee_ID' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'Admin',
                'action' => 'Assigned',
                'target_type' => 'Asset',
                'target_name' => $asset->Asset_Name,
                'details' => "Assigned to: {$assignedToUser->name} (ID: {$assignedToUser->id}) via Ticket #{$ticket->id}",
            ]);

            // Notify user about the asset assignment
            if ($assignedToUser && $assignedToUser->email) {
                $subject = "Your requested asset has been assigned";
                $details = "The asset '{$asset->Asset_Name}' (S/N: {$asset->Serial_No}) has been assigned to you to resolve Ticket #{$ticket->id}.\n\n";
                if (!empty($bundleItems)) {
                    $details .= "The following items were bundled with your assignment:\n" . implode("\n", $bundleItems) . "\n\n";
                }
                $details .= "Please log in for more details. The ticket is now considered resolved.";

                // In a real application, this would use a Mailable class.
                Mail::raw($details, function ($message) use ($assignedToUser, $subject) {
                    $message->to($assignedToUser->email)->subject($subject);
                });
            }

            return $bundleItems;
        });

        return response()->json([
            'message' => 'Asset assigned successfully to requester.',
            'ticket' => $ticket->fresh()->load(['user', 'issue.asset', 'status']),
            'asset' => $asset->fresh()->load('status'),
            'bundle_items' => $bundleSummary,
        ]);
    }

    public function createReturnRequest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => 'nullable|integer|exists:assets,id',
            'items' => 'nullable|array',
            'items.*.type' => 'required_with:items|string|in:asset,component,accessory,license,consumable',
            'items.*.id' => 'required_with:items|integer',
            'condition' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:2000',
        ]);

        $user = $request->user() ?? Auth::user();
        $asset = null;
        if (!empty($data['asset_id'])) {
            $asset = Asset::findOrFail($data['asset_id']);
            if ((int) $asset->Employee_ID !== (int) $user->id) {
                return response()->json([
                    'message' => 'You can only return assets currently assigned to you.'
                ], 403);
            }
        }

        // require either an asset or some items
        if (!$asset && empty($data['items'])) {
            return response()->json([
                'message' => 'Please specify an asset or at least one item to return.'
            ], 422);
        }

        // build ticket description, include items list when appropriate
        $description = "Workflow Type: RETURN\n";
        if ($asset) {
            $description .= "Asset ID: {$asset->id}\n"
                         . "Asset Name: {$asset->Asset_Name}\n";
        } else {
            $description .= "Items: " . collect($data['items'] ?? [])
                ->map(fn($i) => "{$i['type']} #{$i['id']}")
                ->implode(', ') . "\n";
        }
        $description .= "Condition: " . ($data['condition'] ?? 'Not specified') . "\n"
                      . "Reason: " . ($data['reason'] ?? 'No reason provided');

        $ticket = Ticket::create([
            'Employee_ID' => $user->id,
            'Status_ID' => Status::firstOf(['Pending','New','Open']) ?? 1,
            'Priority' => 'medium',
            'Description' => $description,
        ]);

        ActivityLog::create([
            'asset_id' => $asset?->id,
            'Employee_ID' => $user->id,
            'user_name' => $user->name ?? 'User',
            'action' => 'Requested Return',
            'target_type' => $asset ? 'Asset' : 'Mixed Items',
            'target_name' => $asset ? $asset->Asset_Name : 'Mixed Items',
            'details' => "Return request submitted via Workflow Hub. Ticket #{$ticket->id}",
        ]);

        return response()->json([
            'message' => 'Return request submitted successfully.',
            'ticket' => $ticket,
            'items' => $data['items'] ?? [],
        ], 201);
    }

    /**
     * Helper used internally to look up status IDs by name.  Accepts
     * an array of possible names and returns the first matching ID or null.
     * This frees the rest of the code from hard‑coding numeric constants.
     */

    public function getWorkflowQueues(Request $request): JsonResponse
    {
        $user = $request->user() ?? Auth::user();

        if (($user->role ?? 'user') !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $equipmentRequests = Ticket::with(['user', 'issue.asset', 'status'])
            ->where('Description', 'like', 'Request Category:%')
            ->latest()
            ->get();

        $returnRequests = Ticket::with(['user', 'issue.asset', 'status'])
            ->where('Description', 'like', 'Workflow Type: RETURN%')
            ->latest()
            ->get()
            ->map(function ($t) {
                $array = $t->toArray();
                if (preg_match('/Items: ([^\n]+)/', $t->Description, $m)) {
                    $array['items'] = array_map('trim', explode(',', $m[1]));
                }
                return $array;
            });

        return response()->json([
            'equipment_requests' => $equipmentRequests,
            'return_requests' => $returnRequests,
        ]);
    }

    public function getMyReturnableAssets(Request $request): JsonResponse
    {
        $user = $request->user() ?? Auth::user();

        // return a shape similar to other endpoints (dashboard, transfer forms)
        // so frontend tables can rely on consistent field names and avoid
        // mismatched columns.
        $assets = Asset::query()
            ->with('status')
            ->where('Employee_ID', $user->id)
            ->select('id', 'Asset_Name', 'Serial_No', 'Asset_Category', 'Status_ID')
            ->latest()
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'asset_tag' => 'AST-' . str_pad((string) $a->id, 4, '0', STR_PAD_LEFT),
                'model' => $a->Asset_Name,
                'serial' => $a->Serial_No,
                'category' => $a->Asset_Category,
                'status' => $a->status,
            ]);

        return response()->json($assets);
    }

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
            return response()->json([
                'message' => 'Asset could not be resolved from this return request.'
            ], 422);
        }

        $asset = Asset::findOrFail($assetId);

        DB::transaction(function () use ($ticket, $asset, $data) {
            $storeStatusId = Status::query()
                ->whereIn('Status_Name', ['Ready to Deploy', 'Available'])
                ->value('id');

            $maintenanceAssetStatusId = Status::query()
                ->whereIn('Status_Name', ['Out for Repair', 'Maintenance', 'Pending'])
                ->value('id');

            $ticketClosedStatusId = Status::query()
                ->whereIn('Status_Name', ['Resolved', 'Closed', 'Completed'])
                ->value('id');

            if ($data['disposition'] === 'store') {
                $asset->update([
                    'Employee_ID' => Auth::id(),
                    'Status_ID' => $storeStatusId ?? $asset->Status_ID,
                ]);
            } else {
                $asset->update([
                    'Employee_ID' => Auth::id(),
                    'Status_ID' => $maintenanceAssetStatusId ?? $asset->Status_ID,
                ]);

                Maintenance::create([
                    'Asset_ID' => $asset->id,
                    'Ticket_ID' => $ticket->id,
                    'Request_Date' => now(),
                    'Completion_Date' => null,
                    'Maintenance_Type' => $data['maintenance_type'] ?? 'Inspection after return',
                    'Description' => $data['notes'] ?? 'Created from return workflow disposition.',
                    'Cost' => null,
                    'Status_ID' => $maintenanceAssetStatusId ?? 1,
                    'Maintenance_Date' => now(),
                ]);
            }

            $ticket->update([
                'Status_ID' => $ticketClosedStatusId ?? $ticket->Status_ID,
                'Communication_log' => trim(($ticket->Communication_log ? $ticket->Communication_log . "\n" : '')
                    . 'Return processed by Admin. Disposition: ' . $data['disposition']
                    . ($data['notes'] ? '; Notes: ' . $data['notes'] : '')),
            ]);

            ActivityLog::create([
                'asset_id' => $asset->id,
                'Employee_ID' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'Admin',
                'action' => 'Processed Return',
                'target_type' => 'Asset',
                'target_name' => $asset->Asset_Name,
                'details' => 'Disposition: ' . $data['disposition'] . '; Ticket #' . $ticket->id,
            ]);
        });

        return response()->json([
            'message' => 'Return processed successfully.',
            'ticket' => $ticket->fresh()->load(['user', 'issue.asset', 'status']),
            'asset' => $asset->fresh()->load('status'),
        ]);
    }

    private function extractAssetIdFromDescription(string $description): ?int
    {
        if (preg_match('/Asset ID:\s*(\d+)/i', $description, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}