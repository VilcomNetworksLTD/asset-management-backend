<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Supplier;
use App\Models\Status;

echo "--- Testing User Status ---\n";
$user = User::create([
    'name' => 'Test User Status',
    'email' => 'test_status_' . time() . '@example.com',
    'password' => bcrypt('password'),
    'role' => 'staff',
]);
$user->load('status');
echo "Created User Status: " . ($user->status->Status_Name ?? 'N/A') . " (ID: {$user->Status_ID})\n";

$user->delete();
$user->refresh();
$user->load('status');
echo "Deleted User Status: " . ($user->status->Status_Name ?? 'N/A') . " (ID: {$user->Status_ID})\n";

echo "\n--- Testing Supplier Status ---\n";
$supplier = Supplier::create([
    'Supplier_Name' => 'Test Supplier Status ' . time(),
    'Location' => 'Test Location',
    'Contact' => '123456789',
]);
$supplier->load('status');
echo "Created Supplier Status: " . ($supplier->status->Status_Name ?? 'N/A') . " (ID: {$supplier->Status_ID})\n";

$supplier->delete();
$supplier->refresh();
$supplier->load('status');
echo "Deleted Supplier Status: " . ($supplier->status->Status_Name ?? 'N/A') . " (ID: {$supplier->Status_ID})\n";

// Cleanup
$user->forceDelete();
$supplier->forceDelete();
echo "\nCleanup done.\n";
