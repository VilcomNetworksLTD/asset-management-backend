<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            // General / Asset Statuses
            'Ready to Deploy',
            'Deployed',
            'Archived',
            'Out for Repair',
            'Maintenance',
            'Available',
            'Assigned',
            'In Use',
            'Broken',
            'Lost',
            'Stolen',
            'Non-Deployable',
            'Retired',
            
            // Ticket / Maintenance / Workflow Statuses
            'Pending',
            'New',
            'Open',
            'Closed',
            'Resolved',
            'Completed',
            'Rejected',
            'Declined',
            'Cancelled',
            'In Progress',
            'On Hold',
            'Scheduled',
            'Under Repair',
            'Awaiting Purchase',
            'Approved',
            'Pending Approval',
            'In Transit',
            'Received',
            'Completed with Issues',
            'Active',
            'Deactivated',
        ];

        foreach ($statuses as $name) {
            Status::firstOrCreate(['Status_Name' => $name]);
        }
    }
}