<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $asset;
    protected $statusAvailable;
    protected $statusOutForRepair;
    protected $statusReadyToDeploy;
    protected $statusArchived;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        
        $this->statusAvailable = Status::firstOrCreate(['Status_Name' => 'Available']);
        $this->statusOutForRepair = Status::firstOrCreate(['Status_Name' => 'Out for Repair']);
        $this->statusReadyToDeploy = Status::firstOrCreate(['Status_Name' => 'Ready to Deploy']);
        $this->statusArchived = Status::firstOrCreate(['Status_Name' => 'Archived']);
        
        Status::firstOrCreate(['Status_Name' => 'Scheduled']);
        Status::firstOrCreate(['Status_Name' => 'Completed']);
        Status::firstOrCreate(['Status_Name' => 'Cancelled']);

        $supplier = Supplier::create(["Supplier_Name" => "Acme", "Location" => "Here", "Contact" => "none"]);
        
        $this->asset = Asset::create([
            'Asset_Name' => 'Test Asset',
            'Asset_Category' => 'Equipment',
            'Supplier_ID' => $supplier->id,
            'Status_ID' => $this->statusAvailable->id,
        ]);
    }

    public function test_scheduling_maintenance_updates_asset_status_to_out_for_repair()
    {
        $this->actingAs($this->admin);
        
        $response = $this->postJson('/api/maintenances', [
            'Asset_ID' => $this->asset->id,
            'Maintenance_Type' => 'Preventive',
            'Description' => 'Monthly check',
        ]);

        $response->assertStatus(201);
        $this->assertEquals($this->statusOutForRepair->id, $this->asset->fresh()->Status_ID);
    }

    public function test_completing_maintenance_updates_asset_status_to_ready_to_deploy()
    {
        $maintenance = Maintenance::create([
            'Asset_ID' => $this->asset->id,
            'Maintenance_Type' => 'Repair',
            'Workflow_Status' => 'In Progress'
        ]);

        $this->actingAs($this->admin);
        
        $response = $this->putJson("/api/maintenances/{$maintenance->id}", [
            'Workflow_Status' => 'Completed'
        ]);

        $response->assertStatus(200);
        $this->assertEquals($this->statusReadyToDeploy->id, $this->asset->fresh()->Status_ID);
        $this->assertEquals('Completed', $maintenance->fresh()->Workflow_Status);
    }

    public function test_cancelling_maintenance_updates_asset_status_to_available()
    {
        $maintenance = Maintenance::create([
            'Asset_ID' => $this->asset->id,
            'Maintenance_Type' => 'Repair',
            'Workflow_Status' => 'Scheduled'
        ]);

        $this->actingAs($this->admin);
        
        $response = $this->putJson("/api/maintenances/{$maintenance->id}", [
            'Workflow_Status' => 'Cancelled'
        ]);

        $response->assertStatus(200);
        $this->assertEquals($this->statusAvailable->id, $this->asset->fresh()->Status_ID);
    }

    public function test_archiving_maintenance_updates_both_to_archived()
    {
        $maintenance = Maintenance::create([
            'Asset_ID' => $this->asset->id,
            'Maintenance_Type' => 'Repair',
            'Workflow_Status' => 'Scheduled'
        ]);

        $this->actingAs($this->admin);
        
        $response = $this->postJson("/api/maintenances/{$maintenance->id}/archive");

        $response->assertStatus(200);
        $this->assertEquals($this->statusArchived->id, $this->asset->fresh()->Status_ID);
        $this->assertEquals($this->statusArchived->id, $maintenance->fresh()->Status_ID);
        $this->assertEquals('Archived', $maintenance->fresh()->Workflow_Status);
    }
}
