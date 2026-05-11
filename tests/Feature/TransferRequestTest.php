<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Supplier;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TransferRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_submit_transfer_with_asset_and_items()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'boss@example.com']);
        $status = Status::create(['Status_Name' => 'Available']);
        $supplier = Supplier::create(["Supplier_Name" => "TestCo", "Location" => "Nowhere", "Contact" => "none"]);

        $asset = Asset::create([
            'Asset_Name' => 'Device X',
            'Asset_Category' => 'Gadget',
            'Supplier_ID' => $supplier->id,
            'Status_ID' => $status->id,
            'Employee_ID' => $sender->id,
        ]);

        // extras – use real component record so name lookup works
        $comp = \App\Models\Accessory::create([
            'name' => 'Comp X',
            'category' => 'Generic',
            'serial_no' => 'C-X',
            'total_qty' => 1,
            'remaining_qty' => 1,
            'price' => 0,
            'type' => 'component',
        ]);

        $this->actingAs($sender);
        $payload = [
            'asset_id' => $asset->id,
            'type' => 'transfer',
            'receiver_id' => $receiver->id,
            'items' => [
                ['type' => 'component', 'id' => $comp->id],
            ],
            'sender_condition' => 'good',
        ];

        \Illuminate\Support\Facades\Mail::fake();

        $resp = $this->postJson('/api/transfers/return', $payload);
        $resp->assertStatus(201);
        $body = $resp->json('transfer');
        $this->assertArrayHasKey('items', $body);
        $this->assertCount(1, $body['items']);
        $this->assertEquals('component', $body['items'][0]['type']);
        $this->assertArrayHasKey('name', $body['items'][0]);
        $this->assertStringContainsString('Comp X', $body['items'][0]['name']);

        // email to sender and admin should fire (queued) and not include numeric IDs
        Mail::assertQueued(\Illuminate\Mail\Mailable::class, function (\Illuminate\Mail\Mailable $mail) use ($sender, $admin) {
            $tos = collect($mail->to ?? [])->pluck('address')->all();
            $hasRecipient = in_array($sender->email, $tos) || in_array($admin->email, $tos);
            $noIds = !str_contains($mail->subject, '#')
                      && !str_contains($mail->viewData['bodyText'] ?? '', '#');
            return $hasRecipient && $noIds;
        });

        // ensure the listing endpoint reflects the new transfer
        $list = $this->getJson('/api/transfers')->json();
        $this->assertNotEmpty($list);
        $found = collect($list)->firstWhere('id', $body['id']);
        $this->assertNotNull($found, 'Transfer should appear in list');
        $this->assertArrayHasKey('items', $found);
        $this->assertCount(1, $found['items']);
        $this->assertNotNull($found['asset'], 'Asset should be present when provided');
    }

    public function test_transfer_without_asset_but_with_items_is_allowed()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'boss@example.com']);
        $status = Status::create(['Status_Name' => 'Available']);
        $supplier = Supplier::create(["Supplier_Name" => "TestCo", "Location" => "Nowhere", "Contact" => "none"]);

        // create an accessory record so name can be resolved
        $acc = \App\Models\Accessory::create([
            'name' => 'Accessory Y',
            'category' => 'Generic',
            'model_number' => 'ACC-Y',
            'total_qty' => 1,
            'remaining_qty' => 1,
            'price' => 0,
        ]);

        $this->actingAs($sender);
        \Illuminate\Support\Facades\Mail::fake();

        $resp = $this->postJson('/api/transfers/return', [
            'type' => 'return',
            'items' => [
                ['type' => 'accessory', 'id' => $acc->id],
            ],
            'sender_condition' => 'good',
        ]);
        $resp->assertStatus(201);
        $body = $resp->json('transfer');
        $this->assertEquals('return', $body['type']);
        $this->assertArrayHasKey('items', $body);
        $this->assertCount(1, $body['items']);
        $this->assertArrayHasKey('name', $body['items'][0]);
        $this->assertStringContainsString('Accessory Y', $body['items'][0]['name']);

        Mail::assertQueued(\Illuminate\Mail\Mailable::class, function (\Illuminate\Mail\Mailable $mail) use ($sender, $admin) {
            $tos = collect($mail->to ?? [])->pluck('address')->all();
            return in_array($sender->email, $tos) || in_array($admin->email, $tos);
        });

        // list should show it with items and no asset
        $list = $this->getJson('/api/transfers')->json();
        $found = collect($list)->firstWhere('id', $body['id']);
        $this->assertNotNull($found);
        $this->assertArrayHasKey('items', $found);
        $this->assertCount(1, $found['items']);
        $this->assertNull($found['asset'], 'Asset should be null on item-only request');

        // admin inspects the request to ensure no 500 when asset is missing
        $this->actingAs($admin);
        $resp2 = $this->postJson("/api/admin/transfers/{$body['id']}/complete", [
            'condition' => 'Good',
            'disposition' => 'ready_to_deploy',
            'missing_items' => [],
            'admin_notes' => 'OK',
        ]);
        $resp2->assertStatus(200);
        $updated = $resp2->json('transfer');
        $this->assertEquals('completed', $updated['status']);
        $this->assertNull($updated['asset']);
    }

    public function test_receiver_gets_pending_assignment_after_admin_inspection()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);
        $status = Status::create(['Status_Name' => 'Available']);
        $supplier = Supplier::create(["Supplier_Name" => "TestCo", "Location" => "Nowhere", "Contact" => "none"]);

        $asset = Asset::create([
            'Asset_Name' => 'Phone Z',
            'Asset_Category' => 'Device',
            'Supplier_ID' => $supplier->id,
            'Status_ID' => $status->id,
            'Employee_ID' => $sender->id,
        ]);

        $this->actingAs($sender);
        // include a component so pending assignment shows it
        $comp = \App\Models\Accessory::create([
            'name' => 'Promo USB',
            'category' => 'Accessory',
            'serial_no' => 'USB-1',
            'total_qty' => 1,
            'remaining_qty' => 1,
            'price' => 0,
            'type' => 'component',
        ]);
        $resp = $this->postJson('/api/transfers/return', [
            'asset_id' => $asset->id,
            'type' => 'transfer',
            'receiver_id' => $receiver->id,
            'sender_condition' => 'good',
            'items' => [['type' => 'component', 'id' => $comp->id]],
        ]);
        $resp->assertStatus(201);
        $id = $resp->json('transfer.id');

        // admin inspects - should set workflow_status pending_verification
        $this->actingAs($admin);
        $inspect = $this->postJson("/api/admin/transfers/{$id}/complete", [
            'condition' => 'Good',
            'disposition' => 'ready_to_deploy',
            'missing_items' => [],
            'admin_notes' => null,
        ]);
        $inspect->assertStatus(200);
        $this->assertDatabaseHas('transfers', [
            'id' => $id,
            'Workflow_Status' => 'pending_verification',
        ]);

        // receiver should now see it in pending assignments API
        $this->actingAs($receiver);
        $pending = $this->getJson('/api/my-pending-assignments')->json();
        $this->assertCount(1, $pending);
        $this->assertEquals($id, $pending[0]['id']);
        // asset info must be present
        $this->assertArrayHasKey('asset', $pending[0]);
        $this->assertEquals('Phone Z', $pending[0]['asset']['model']);
        // should contain the component name and detailed item record
        $this->assertArrayHasKey('items', $pending[0]);
        $this->assertCount(1, $pending[0]['items']);
        $this->assertEquals('Promo USB', $pending[0]['items'][0]['name']);
        $this->assertArrayHasKey('details', $pending[0]['items'][0]);
    }

    public function test_inbound_verification_assigns_components_to_receiver()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);
        $status = Status::create(['Status_Name' => 'Available']);
        $supplier = Supplier::create(["Supplier_Name" => "TestCo", "Location" => "Nowhere", "Contact" => "none"]);

        $asset = Asset::create([
            'Asset_Name' => 'Tablet Z',
            'Asset_Category' => 'Gadget',
            'Supplier_ID' => $supplier->id,
            'Status_ID' => $status->id,
            'Employee_ID' => $sender->id,
        ]);

        $comp = \App\Models\Accessory::create([
            'name' => 'Spare RAM',
            'category' => 'Memory',
            'serial_no' => 'RAM-1',
            'total_qty' => 1,
            'remaining_qty' => 1,
            'price' => 0,
            'type' => 'component',
        ]);

        // sender must actually possess the component before transfer
        $sender->components()->attach($comp->id, ['quantity' => 1]);

        $this->actingAs($sender);
        $resp = $this->postJson('/api/transfers/return', [
            'asset_id' => $asset->id,
            'type' => 'transfer',
            'receiver_id' => $receiver->id,
            'sender_condition' => 'good',
            'items' => [['type' => 'component', 'id' => $comp->id]],
        ]);
        $resp->assertStatus(201);
        $id = $resp->json('transfer.id');

        // admin inspection
        $this->actingAs($admin);
        $this->postJson("/api/admin/transfers/{$id}/complete", [
            'condition' => 'Good',
            'disposition' => 'ready_to_deploy',
            'missing_items' => [],
            'admin_notes' => null,
        ]);

        // receiver verifies inbound
        $this->actingAs($receiver);
        $this->postJson("/api/assignments/{$id}/verify", ['status' => 'accepted']);

        // component should now be attached and stock decremented
        $this->assertDatabaseHas('accessory_user', [
            'user_id' => $receiver->id,
            'accessory_id' => $comp->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('accessories', [
            'id' => $comp->id,
            'remaining_qty' => 0,
        ]);
        // original sender should have an entry marked as returned (no active quantity)
        $this->assertDatabaseHas('accessory_user', [
            'user_id' => $sender->id,
            'accessory_id' => $comp->id,
        ]);
        $this->assertDatabaseMissing('accessory_user', [
            'user_id' => $sender->id,
            'accessory_id' => $comp->id,
            'returned_at' => null,
        ]);
    }
}
