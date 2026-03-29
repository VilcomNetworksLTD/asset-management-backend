<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'Ready to Deploy',
            'Deployed',
            'Archived',
            'Out for Repair',
            'Under Repair',
            'Missing',
            'Pending',
            'Available',
            'Disposed',
            'Open',
            'New',
            'In Progress',
            'On Hold',
            'Completed',
            'Cancelled',
            'Resolved',
            'Closed',
            'Active',
            'Deactivated',
            'Under Repair',
        ];

        foreach ($statuses as $name) {
            Status::firstOrCreate(['Status_Name' => $name]);
        }
    }
}
