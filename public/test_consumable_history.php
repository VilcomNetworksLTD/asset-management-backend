<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$history = \App\Models\AssetConsumable::with(['asset', 'consumable'])->take(1)->get();
echo json_encode($history);
