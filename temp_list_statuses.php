<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$statuses = \App\Models\Status::all();
foreach ($statuses as $status) {
    echo "ID: {$status->id}, Name: {$status->Status_Name}\n";
}
