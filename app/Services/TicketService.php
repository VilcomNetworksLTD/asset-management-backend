<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\Issue;
use App\Models\Maintenance;
use App\Models\Status;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TicketService
{
    /**
     * Handles the standard update/resolution of a ticket
     */
    public function resolveTicket(int $id, string $communication, int $statusId): Ticket
    {
        return DB::transaction(function () use ($id, $communication, $statusId) {
            $ticket = Ticket::findOrFail($id);
            
            $ticket->update([
                'Status_ID' => $statusId,
                'Communication_log' => trim(($ticket->Communication_log ? $ticket->Communication_log . "\n" : '') . $communication),
            ]);

            // If the ticket is linked to an Issue, update the issue status as well
            if (Schema::hasColumn('tickets', 'Issue_ID') && $ticket->Issue_ID) {
                Issue::where('id', $ticket->Issue_ID)->update(['Status_ID' => $statusId]);
            }

            return $ticket;
        });
    }

    /**
     * Handles complex asset and accessory assignment
     */
    public function assignAssetToTicket(int $ticketId, array $data): array
    {
        return DB::transaction(function () use ($ticketId, $data) {
            $ticket = Ticket::findOrFail($ticketId);
            $asset = Asset::findOrFail($data['asset_id']);

            $deployedStatusId = Status::whereIn('Status_Name', ['Deployed', 'Assigned', 'In Use'])->value('id');
            $resolvedStatusId = Status::whereIn('Status_Name', ['Resolved', 'Closed', 'Completed'])->value('id');

            // 1. Update Asset Ownership
            $asset->update([
                'Employee_ID' => $ticket->Employee_ID,
                'Status_ID' => $deployedStatusId ?? $asset->Status_ID,
            ]);

            $bundleItems = [];

            // 2. Handle Accessories
            foreach (($data['accessory_allocations'] ?? []) as $item) {
                $accessory = Accessory::lockForUpdate()->findOrFail($item['id']);
                if ((int) $accessory->remaining_qty < (int) $item['qty']) {
                    throw ValidationException::withMessages(['accessories' => "Insufficient stock for {$accessory->name}"]);
                }
                $accessory->decrement('remaining_qty', $item['qty']);
                $bundleItems[] = "Accessory: {$accessory->name} x{$item['qty']}";
            }

            // 3. Handle Consumables
            foreach (($data['consumable_allocations'] ?? []) as $item) {
                $consumable = Consumable::lockForUpdate()->findOrFail($item['id']);
                if ((int) $consumable->in_stock < (int) $item['qty']) {
                    throw ValidationException::withMessages(['consumables' => "Insufficient stock for {$consumable->item_name}"]);
                }
                $consumable->decrement('in_stock', $item['qty']);
                $bundleItems[] = "Consumable: {$consumable->item_name} x{$item['qty']}";
            }

            // 4. Update Ticket Logs
            $bundleLine = empty($bundleItems) ? null : 'Bundle Items: ' . implode(', ', $bundleItems);
            $log = trim(($data['communication'] ?? 'Asset assigned by admin') . ($bundleLine ? "\n" . $bundleLine : ''));

            $ticket->update([
                'Status_ID' => $resolvedStatusId ?? $ticket->Status_ID,
                'Communication_log' => trim(($ticket->Communication_log ? $ticket->Communication_log . "\n" : '') . $log),
            ]);

            // 5. Update linked Issue
            if (Schema::hasColumn('tickets', 'Issue_ID') && $ticket->Issue_ID) {
                Issue::where('id', $ticket->Issue_ID)->update([
                    'Asset_ID' => $asset->id,
                    'Status_ID' => $resolvedStatusId ?? 1,
                ]);
            }

            // 6. Log Activity
            ActivityLog::create([
                'Employee_ID' => Auth::id(),
                'user_name' => Auth::user()->name ?? 'Admin',
                'action' => 'Assigned',
                'target_type' => 'Asset',
                'target_name' => $asset->Asset_Name,
                'details' => "Assigned to Employee_ID {$ticket->Employee_ID} via Ticket #{$ticket->id}",
            ]);

            return ['ticket' => $ticket, 'asset' => $asset, 'bundle' => $bundleItems];
        });
    }
}