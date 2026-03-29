<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Consumable;
use App\Models\Asset;
use App\Models\Supplier;
use App\Models\Status;

class TonerSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clear existing non-toner consumables if any
        Consumable::truncate();

        // 2. Populate with Toners
        $toners = [
            ['item_name' => 'HP 85A Black Toner', 'category' => 'Toner', 'in_stock' => 15, 'price' => 5000, 'min_amt' => 5],
            ['item_name' => 'HP 26A Black Toner', 'category' => 'Toner', 'in_stock' => 10, 'price' => 12000, 'min_amt' => 3],
            ['item_name' => 'HP 83A Black Toner', 'category' => 'Toner', 'in_stock' => 20, 'price' => 4500, 'min_amt' => 8],
            ['item_name' => 'HP 05A Black Toner', 'category' => 'Toner', 'in_stock' => 5, 'price' => 9500, 'min_amt' => 2],
            ['item_name' => 'HP 12A Black Toner', 'category' => 'Toner', 'in_stock' => 25, 'price' => 4800, 'min_amt' => 10],
            ['item_name' => 'HP 80A Black Toner', 'category' => 'Toner', 'in_stock' => 12, 'price' => 11500, 'min_amt' => 4],
            ['item_name' => 'HP 30A Black Toner', 'category' => 'Toner', 'in_stock' => 18, 'price' => 7200, 'min_amt' => 6],
            ['item_name' => 'Canon 054 Cyan Toner', 'category' => 'Toner', 'in_stock' => 8, 'price' => 7500, 'min_amt' => 3],
            ['item_name' => 'Canon 054 Magenta Toner', 'category' => 'Toner', 'in_stock' => 8, 'price' => 7500, 'min_amt' => 3],
            ['item_name' => 'Canon 054 Yellow Toner', 'category' => 'Toner', 'in_stock' => 8, 'price' => 7500, 'min_amt' => 3],
            ['item_name' => 'Canon 054 Black Toner', 'category' => 'Toner', 'in_stock' => 12, 'price' => 6000, 'min_amt' => 4],
            ['item_name' => 'Canon 737 Black Toner', 'category' => 'Toner', 'in_stock' => 15, 'price' => 3500, 'min_amt' => 5],
            ['item_name' => 'Canon 303 Black Toner', 'category' => 'Toner', 'in_stock' => 22, 'price' => 4200, 'min_amt' => 8],
            ['item_name' => 'Brother TN-2410 Black', 'category' => 'Toner', 'in_stock' => 14, 'price' => 6500, 'min_amt' => 5],
            ['item_name' => 'Brother TN-3480 Black', 'category' => 'Toner', 'in_stock' => 9, 'price' => 15500, 'min_amt' => 3],
            ['item_name' => 'Kyocera TK-1170 Black', 'category' => 'Toner', 'in_stock' => 11, 'price' => 8900, 'min_amt' => 4],
            ['item_name' => 'Kyocera TK-3160 Black', 'category' => 'Toner', 'in_stock' => 6, 'price' => 14200, 'min_amt' => 2],
            ['item_name' => 'Samsung MLT-D111S Black', 'category' => 'Toner', 'in_stock' => 13, 'price' => 7800, 'min_amt' => 4],
            ['item_name' => 'Epson L-Series Black Ink', 'category' => 'Ink', 'in_stock' => 30, 'price' => 1500, 'min_amt' => 10],
            ['item_name' => 'Epson L-Series Cyan Ink', 'category' => 'Ink', 'in_stock' => 15, 'price' => 1500, 'min_amt' => 5],
            ['item_name' => 'Epson L-Series Magenta Ink', 'category' => 'Ink', 'in_stock' => 15, 'price' => 1500, 'min_amt' => 5],
            ['item_name' => 'Epson L-Series Yellow Ink', 'category' => 'Ink', 'in_stock' => 15, 'price' => 1500, 'min_amt' => 5],
        ];

        foreach ($toners as $toner) {
            Consumable::create($toner);
        }

        // 3. Add more Printers
        $supplierId = Supplier::first()->id ?? 1;
        $availableStatusId = Status::where('Status_Name', 'Available')->first()->id ?? 1;

        $printers = [
            ['Asset_Name' => 'HP LaserJet Pro M15w', 'Asset_Category' => 'Printer', 'Serial_No' => 'HP-PRNT-001', 'Supplier_ID' => $supplierId, 'Status_ID' => $availableStatusId, 'Price' => 25000, 'location' => 'Main Office'],
            ['Asset_Name' => 'Canon imageCLASS LBP623Cdw', 'Asset_Category' => 'Printer', 'Serial_No' => 'CN-PRNT-002', 'Supplier_ID' => $supplierId, 'Status_ID' => $availableStatusId, 'Price' => 45000, 'location' => 'Marketing Dept'],
            ['Asset_Name' => 'Epson EcoTank L3150', 'Asset_Category' => 'Printer', 'Serial_No' => 'EP-PRNT-003', 'Supplier_ID' => $supplierId, 'Status_ID' => $availableStatusId, 'Price' => 22000, 'location' => 'Reception'],
            ['Asset_Name' => 'HP Color LaserJet Pro M255dw', 'Asset_Category' => 'Printer', 'Serial_No' => 'HP-PRNT-004', 'Supplier_ID' => $supplierId, 'Status_ID' => $availableStatusId, 'Price' => 35000, 'location' => 'IT Lab'],
        ];

        foreach ($printers as $printer) {
            Asset::create($printer);
        }
    }
}
