<?php

use Illuminate\Support\Facades\DB;
use App\Models\Status;

$statuses = [
    'Ready to Deploy',
    'Deployed',
    'Archived',
    'Out for Repair',
    'In Maintenance',
    'Pending',
    'Rejected',
    'Escalated to Management',
    'Completed',
    'Available',
    'Broken',
    'Lost',
    'Stolen'
];

foreach ($statuses as $name) {
    if (!DB::table('statuses')->where('Status_Name', $name)->exists()) {
        DB::table('statuses')->insert([
            'Status_Name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "Created status: $name\n";
    } else {
        echo "Status already exists: $name\n";
    }
}
