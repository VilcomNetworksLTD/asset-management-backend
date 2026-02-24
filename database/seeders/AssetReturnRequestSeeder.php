<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\ReturnRequest;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AssetReturnRequestSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin.returns@example.com'],
            [
                'name' => 'Returns Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        $staffA = User::firstOrCreate(
            ['email' => 'staff.a@example.com'],
            [
                'name' => 'Staff A',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        $staffB = User::firstOrCreate(
            ['email' => 'staff.b@example.com'],
            [
                'name' => 'Staff B',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        $supplier = Supplier::firstOrCreate(
            ['Supplier_Name' => 'Seeded Supplier Ltd'],
            [
                'Location' => 'Nairobi',
                'Contact' => '+254700000000',
            ]
        );

        $pendingStatus = $this->firstOrCreateStatus('Pending');
        $deployedStatus = $this->firstOrCreateStatus('Deployed');
        $readyStatus = $this->firstOrCreateStatus('Ready to Deploy');

        $assetA = Asset::updateOrCreate(
            ['Serial_No' => 'RET-SEED-SN-001'],
            [
                'Asset_Name' => 'Dell Latitude 5420',
                'Asset_Category' => 'Laptop',
                'Supplier_ID' => $supplier->id,
                'Employee_ID' => $staffA->id,
                'Status_ID' => $deployedStatus->id,
                'Price' => 95000,
            ]
        );

        $assetB = Asset::updateOrCreate(
            ['Serial_No' => 'RET-SEED-SN-002'],
            [
                'Asset_Name' => 'HP ProBook 440',
                'Asset_Category' => 'Laptop',
                'Supplier_ID' => $supplier->id,
                'Employee_ID' => $staffB->id,
                'Status_ID' => $deployedStatus->id,
                'Price' => 87000,
            ]
        );

        ReturnRequest::updateOrCreate(
            ['Notes' => 'SEED-RETURN-001'],
            [
                'Asset_ID' => $assetA->id,
                'Employee_ID' => $staffA->id,
                'Sender_ID' => $staffA->id,
                'Status_ID' => $pendingStatus->id,
                'Request_Date' => now()->subDays(2),
                'Workflow_Status' => 'pending_inspection',
                'Sender_Condition' => 'Good',
                'Missing_Items' => ['Laptop Sleeve'],
                'Notes' => 'SEED-RETURN-001',
            ]
        );

        ReturnRequest::updateOrCreate(
            ['Notes' => 'SEED-RETURN-002'],
            [
                'Asset_ID' => $assetB->id,
                'Employee_ID' => $staffB->id,
                'Sender_ID' => $staffB->id,
                'Status_ID' => $pendingStatus->id,
                'Request_Date' => now()->subDays(5),
                'Workflow_Status' => 'completed',
                'Sender_Condition' => 'Damaged',
                'Admin_Condition' => 'Fair',
                'Missing_Items' => ['Charger'],
                'Notes' => 'SEED-RETURN-002',
                'Actioned_By' => $admin->id,
                'Actioned_At' => now()->subDays(4),
            ]
        );

        ReturnRequest::updateOrCreate(
            ['Notes' => 'SEED-RETURN-003'],
            [
                'Asset_ID' => $assetA->id,
                'Employee_ID' => $staffA->id,
                'Sender_ID' => $staffA->id,
                'Status_ID' => $pendingStatus->id,
                'Request_Date' => now()->subDay(),
                'Workflow_Status' => 'pending',
                'Sender_Condition' => 'Good',
                'Admin_Condition' => null,
                'Missing_Items' => [],
                'Notes' => 'SEED-RETURN-003',
            ]
        );

        // Keep one asset in ready state to make seeded data look coherent with completed returns.
        $assetB->update([
            'Employee_ID' => $admin->id,
            'Status_ID' => $readyStatus->id,
        ]);
    }

    private function firstOrCreateStatus(string $name): Status
    {
        return Status::firstOrCreate(['Status_Name' => $name]);
    }
}
