<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_ticket_with_asset_appears_in_queue()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $status = Status::create(['Status_Name' => 'Available']);
        $supplier = \App\Models\Supplier::create([
            'Supplier_Name' => 'Test Supplier',
            'Location' => 'Here',
            'Contact' => 'none',
        ]);
        $asset = Asset::create([
            'Asset_Name' => 'Gadget',
            'Asset_Category' => 'Tool',
            'Supplier_ID' => $supplier->id,
            'Status_ID' => $status->id,
            'Employee_ID' => $user->id,
        ]);

        $resp = $this->postJson('/api/workflow/returns', [
            'asset_id' => $asset->id,
            'condition' => 'good',
        ]);
        $resp->assertStatus(201)
             ->assertJson(['message' => 'Return request submitted successfully.']);

        // admin is the only one who can view workflow queues
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $queue = $this->getJson('/api/workflow/queues')->json('return_requests');
        $this->assertCount(1, $queue);
        $this->assertStringContainsString('Asset ID: ' . $asset->id, $queue[0]['Description']);
        $this->assertArrayNotHasKey('items', $queue[0]);
    }

    public function test_return_ticket_with_items_shows_item_list()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // create fake "items" stored in the assets table for demonstration
        $supplierId = 1;
        $status = Status::create(['Status_Name' => 'Available']);
        $supplier = \App\Models\Supplier::create([
            'Supplier_Name' => 'Licenser',
            'Location' => 'Here',
            'Contact' => 'none',
        ]);

        $license = Asset::create([
            'Asset_Name' => 'License X',
            'Asset_Category' => 'License',
            'Supplier_ID' => $supplier->id,
            'Status_ID' => $status->id,
            'Employee_ID' => $user->id,
        ]);

        $payload = [
            'items' => [
                ['type' => 'license', 'id' => $license->id],
            ],
            'condition' => 'n/a',
        ];

        $resp = $this->postJson('/api/workflow/returns', $payload);
        $resp->assertStatus(201)
             ->assertJson(['message' => 'Return request submitted successfully.'])
             ->assertJson(['items' => $payload['items']]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $queue = $this->getJson('/api/workflow/queues')->json('return_requests');
        $this->assertCount(1, $queue);
        $this->assertArrayHasKey('items', $queue[0]);
        $this->assertEquals(['license #'.$license->id], $queue[0]['items']);
    }

    public function test_ticket_status_changes_automatically_on_actions()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // ensure the statuses we rely on exist (database is reset each test)
        foreach (['Pending', 'New', 'Open', 'Resolved', 'Closed', 'Completed'] as $name) {
            Status::firstOrCreate(['Status_Name' => $name]);
        }

        // create a ticket
        $resp = $this->postJson('/api/tickets', [
            'subject' => 'Printer issue',
            'description' => 'My printer is broken',
            'priority' => 'high',
        ]);
        $resp->assertStatus(201);
        $ticketId = $resp->json('id');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // resolve without sending any status_id
        $this->putJson("/api/tickets/{$ticketId}", [
            'action' => 'resolve',
            'communication' => 'Fixed the printer.'
        ])->assertStatus(200);

        $resolvedId = Status::firstOf(['Resolved', 'Closed', 'Completed']);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticketId,
            'Status_ID' => $resolvedId,
        ]);

        // reopen the ticket
        $this->putJson("/api/tickets/{$ticketId}", [
            'action' => 'reopen',
            'communication' => 'User says problem persists.'
        ])->assertStatus(200);

        $openId = Status::firstOf(['Pending', 'Open', 'New']);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticketId,
            'Status_ID' => $openId,
        ]);
    }
}
