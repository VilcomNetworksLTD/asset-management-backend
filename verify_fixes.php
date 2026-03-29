<?php

define('LARAVEL_START', microtime(true));

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Asset;
use App\Models\Category;
use App\Models\Status;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Http\Controllers\AssetController;
use App\Services\AssetService;
use App\Services\BarcodeService;

$assetService = new AssetService(new BarcodeService());
$barcodeService = new BarcodeService();
$controller = new AssetController($assetService, $barcodeService);

echo "--- Testing Asset Search by Category Name ---\n";
// Create a temporary category
$category = Category::create([
    'name' => 'Test Category ' . uniqid(),
    'fields' => [['key' => 'test_field', 'label' => 'Test Field', 'type' => 'text']]
]);

// Create an asset in that category
$asset = Asset::create([
    'Asset_Name' => 'Test Asset ' . uniqid(),
    'category_id' => $category->id,
    'Asset_Category' => $category->name,
    'Supplier_ID' => Supplier::first()?->id ?? 1,
    'Status_ID' => Status::first()?->id ?? 1,
]);

$request = new Request(['category' => $category->name]);
$response = $controller->list($request);
$data = json_decode($response->getContent(), true);

$found = false;
foreach ($data['data'] as $item) {
    if ($item['id'] == $asset->id) {
        $found = true;
        break;
    }
}

if ($found) {
    echo "SUCCESS: Asset found by category name.\n";
} else {
    echo "FAILURE: Asset NOT found by category name.\n";
}

echo "\n--- Testing Asset Store with Custom Attributes ---\n";
$storeData = [
    'Asset_Name' => 'Test Asset with Specs',
    'category_id' => $category->id,
    'Supplier_ID' => Supplier::first()?->id ?? 1,
    'custom_attributes' => ['test_field' => 'Test Value']
];

$storeRequest = new Request($storeData);
try {
    $storeResponse = $controller->store($storeRequest);
    $newAsset = json_decode($storeResponse->getContent(), true);
    
    if (isset($newAsset['custom_attributes']['test_field']) && $newAsset['custom_attributes']['test_field'] === 'Test Value') {
        echo "SUCCESS: custom_attributes saved correctly.\n";
    } else {
        echo "FAILURE: custom_attributes NOT saved.\n";
        print_r($newAsset['custom_attributes']);
    }
} catch (\Exception $e) {
    echo "ERROR during store: " . $e->getMessage() . "\n";
}

// Cleanup
$asset->delete();
$category->delete();
if (isset($newAsset['id'])) {
    Asset::find($newAsset['id'])?->delete();
}
