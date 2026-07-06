<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\Status;

class TicketObserver
{
    /**
     * Handle the Ticket "creating" event.
     */
    public function creating(Ticket $ticket): void
    {
        if (empty($ticket->Status_ID)) {
            $ticket->Status_ID = Status::firstOf(['New', 'Pending', 'Open']) ?? 1;
        }
    }

    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        \App\Models\ActivityLog::create([
            'Employee_ID' => $user ? $user->id : $ticket->Employee_ID,
            'user_name' => $user ? $user->name : 'System',
            'action' => 'Ticket Created',
            'target_type' => 'Ticket',
            'target_name' => '#' . $ticket->id,
            'details' => "Support ticket raised: " . ($ticket->Subject ?? 'No Subject'),
        ]);
    }

    /**
     * Handle the Ticket "saved" event.
     */
    public function saved(Ticket $ticket): void
    {
        if ($ticket->wasChanged('Status_ID')) {
            $status = Status::find($ticket->Status_ID);
            if ($status) {
                $statusName = strtolower($status->Status_Name);
                $repairStatuses = ['under repair', 'out for repair', 'maintenance'];
                $resolvedStatuses = ['solved', 'resolved', 'closed', 'completed'];

                // Find linked asset
                $assetId = null;
                if ($ticket->issue) {
                    $assetId = $ticket->issue->Asset_ID;
                }

                if (!$assetId && $ticket->Description) {
                    if (preg_match('/Asset ID:\s*(\d+)/i', $ticket->Description, $matches)) {
                        $assetId = (int) $matches[1];
                    }
                }

                // Log ticket status update in ActivityLog
                $user = \Illuminate\Support\Facades\Auth::user();
                $actionMap = [
                    'solved' => 'Ticket Resolved',
                    'resolved' => 'Ticket Resolved',
                    'closed' => 'Ticket Closed',
                    'rejected' => 'Ticket Rejected',
                    'declined' => 'Ticket Rejected',
                    'escalated' => 'Ticket Escalated',
                    'escalated to management' => 'Ticket Escalated',
                    'awaiting purchase' => 'Ticket Escalated',
                ];
                
                $action = $actionMap[$statusName] ?? 'Ticket Status Updated';
                $details = "Ticket status changed to: " . $status->Status_Name;
                if (($statusName === 'rejected' || $statusName === 'declined') && $ticket->rejection_reason) {
                    $details .= ". Reason: " . $ticket->rejection_reason;
                }

                \App\Models\ActivityLog::create([
                    'asset_id' => $assetId,
                    'Employee_ID' => $user ? $user->id : $ticket->Employee_ID,
                    'user_name' => $user ? $user->name : 'System',
                    'action' => $action,
                    'target_type' => 'Ticket',
                    'target_name' => '#' . $ticket->id,
                    'details' => $details,
                ]);

                if ($assetId) {
                    $asset = \App\Models\Asset::find($assetId);
                    if ($asset) {
                        if (in_array($statusName, $repairStatuses)) {
                            $outForRepairStatus = Status::whereRaw('LOWER(Status_Name) = "out for repair"')->first()
                                ?? Status::whereRaw('LOWER(Status_Name) = "maintenance"')->first();
                            if ($outForRepairStatus) {
                                $asset->update(['Status_ID' => $outForRepairStatus->id]);
                            }
                        } elseif (in_array($statusName, $resolvedStatuses)) {
                            $outForRepairStatusIds = Status::whereRaw('LOWER(Status_Name) IN ("out for repair", "maintenance")')->pluck('id')->toArray();
                            if (in_array($asset->Status_ID, $outForRepairStatusIds)) {
                                $newStatusName = $asset->Employee_ID ? 'Deployed' : 'Available';
                                $newStatus = Status::whereRaw('LOWER(Status_Name) = ?', [strtolower($newStatusName)])->first();
                                if ($newStatus) {
                                    $asset->update(['Status_ID' => $newStatus->id]);
                                }
                            }
                        }
                    }
                }

                // -------------------------------------------------------
                // Sync the linked Maintenance record (Repairs page) status
                // -------------------------------------------------------
                $linkedMaintenance = \App\Models\Maintenance::where('Ticket_ID', $ticket->id)
                    ->whereNull('deleted_at')
                    ->latest()
                    ->first();

                if ($linkedMaintenance) {
                    // Map ticket status → maintenance workflow status
                    $terminalResolvedStatuses = ['solved', 'resolved', 'closed', 'completed', 'finalized'];
                    $terminalRejectedStatuses = ['rejected', 'declined', 'cancelled'];
                    $repairWorkflowStatuses   = ['under repair', 'out for repair', 'maintenance', 'in progress'];

                    if (in_array($statusName, $terminalResolvedStatuses)) {
                        // Ticket resolved → mark maintenance as Solved/Completed
                        $linkedMaintenance->transitionTo(
                            \App\Models\Maintenance::WORKFLOW_SOLVED,
                            $user
                        );
                    } elseif (in_array($statusName, $terminalRejectedStatuses)) {
                        // Ticket rejected → cancel the maintenance
                        $linkedMaintenance->transitionTo(
                            \App\Models\Maintenance::WORKFLOW_CANCELLED,
                            $user
                        );
                    } elseif (in_array($statusName, $repairWorkflowStatuses)) {
                        // Ticket moved to repair → ensure maintenance shows Out for Repair
                        $linkedMaintenance->transitionTo(
                            \App\Models\Maintenance::WORKFLOW_OUT_FOR_REPAIR,
                            $user
                        );
                    } elseif (str_contains($statusName, 'escalat') || str_contains($statusName, 'awaiting')) {
                        // Ticket escalated to purchase → maintenance put on hold
                        $linkedMaintenance->transitionTo(
                            \App\Models\Maintenance::WORKFLOW_ON_HOLD,
                            $user
                        );
                    }
                    // For other status changes (pending, open, etc.), no maintenance transition needed
                }
            }
        }
    }
}
