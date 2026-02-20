<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Consumable;
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
            'requested_category' => 'nullable|string|max:255|required_without:asset_id',
            'description' => 'required|string|max:2000',
            'priority' => 'nullable|string|in:low,medium,high',
        ]);

        $user = $request->user() ?? Auth::user();

        $ticket = DB::transaction(function () use ($data, $user) {
            // Generic equipment request path (no specific asset selected by staff)
            if (empty($data['asset_id'])) {
                return Ticket::create([
                    'Employee_ID' => $user->id,
                    'Status_ID' => 1,
                    'Priority' => $data['priority'] ?? 'medium',
                    'Description' => 'Request Category: ' . $data['requested_category']
                        . "\nRequest Details: " . $data['description'],
                ]);
            }

            $issue = Issue::create([
                'Employee_ID' => $user->id,
                'Asset_ID' => $data['asset_id'],
                'Issue_Description' => $data['description'],
                'Status_ID' => 1,
            ]);

            $ticketPayload = [
                'Employee_ID' => $user->id,
                'Status_ID' => 1,
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

        return response()->json($ticket->load(['user', 'issue.asset', 'status']), 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'communication' => 'required|string',
            'status_id'     => 'required|integer'
        ]);

        $this->ticketService->resolveTicket(
            $id, 
            $request->communication, 
            $request->status_id
        );

        return response()->json(['message' => 'Ticket and Issue updated successfully']);
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

        $ticket = Ticket::findOrFail($id);
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

            ActivityLog::create([
                'Employee_ID' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'Admin',
                'action' => 'Assigned',
                'target_type' => 'Asset',
                'target_name' => $asset->Asset_Name,
                'details' => 'Assigned to Employee_ID ' . $ticket->Employee_ID . ' via Request Ticket #' . $ticket->id,
            ]);

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
            'asset_id' => 'required|integer|exists:assets,id',
            'condition' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:2000',
        ]);

        $user = $request->user() ?? Auth::user();
        $asset = Asset::findOrFail($data['asset_id']);

        if ((int) $asset->Employee_ID !== (int) $user->id) {
            return response()->json([
                'message' => 'You can only return assets currently assigned to you.'
            ], 403);
        }

        $ticket = Ticket::create([
            'Employee_ID' => $user->id,
            'Status_ID' => 1,
            'Priority' => 'medium',
            'Description' => "Workflow Type: RETURN\n"
                . "Asset ID: {$asset->id}\n"
                . "Asset Name: {$asset->Asset_Name}\n"
                . "Condition: " . ($data['condition'] ?? 'Not specified') . "\n"
                . "Reason: " . ($data['reason'] ?? 'No reason provided'),
        ]);

        ActivityLog::create([
            'Employee_ID' => $user->id,
            'user_name' => $user->name ?? 'User',
            'action' => 'Requested Return',
            'target_type' => 'Asset',
            'target_name' => $asset->Asset_Name,
            'details' => "Return request submitted via Workflow Hub. Ticket #{$ticket->id}",
        ]);

        return response()->json([
            'message' => 'Return request submitted successfully.',
            'ticket' => $ticket,
        ], 201);
    }

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
            ->get();

        return response()->json([
            'equipment_requests' => $equipmentRequests,
            'return_requests' => $returnRequests,
        ]);
    }

    public function getMyReturnableAssets(Request $request): JsonResponse
    {
        $user = $request->user() ?? Auth::user();

        $assets = Asset::query()
            ->with('status')
            ->where('Employee_ID', $user->id)
            ->select('id', 'Asset_Name', 'Serial_No', 'Asset_Category', 'Status_ID')
            ->latest()
            ->get();

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