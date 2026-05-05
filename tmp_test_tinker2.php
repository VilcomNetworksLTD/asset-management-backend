<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();
App\Models\ActivityLog::create(['user_name'=>'Test','action'=>'test','target_type'=>'test','target_name'=>'test']);
$response = App\Models\ActivityLog::with('user')->latest()->paginate(50)->toArray();
echo json_encode($response, JSON_PRETTY_PRINT);
