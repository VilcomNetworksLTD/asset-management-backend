<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Supplier;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReturnRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_inspection_accepts_return_and_unassigns_asset()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        // create supporting records
        $status = Status::create(['Status_Name' => 'Available']);
        $supplier = Supplier::create(["Supplier_Name" => "Acme", "Location" => "Nowhere", "Contact" => "none"]);

        // create an asset that belongs to the user
        $asset = Asset::create([
            'Asset_Name' => 'Test Device',
            'Asset_Category' => 'Laptop',
            'Supplier_ID' => $supplier->id,
            'Status_ID' => $status->id,
            'Employee_ID' => $user->id,
        ]);

        // start mail faking to verify notifications
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'boss@example.com']);
        \Illuminate\Support\Facades\Mail::fake();

        $this->actingAs($user);
        $response = $this->postJson('/api/return-requests', [
            'asset_id' => $asset->id,
            'sender_condition' => 'good',
        ]);
        $response->assertStatus(201);
        $reqId = $response->json('return_request.id');

        // user should have gotten a confirmation email and admins notified
        Mail::assertQueued(\Illuminate\Mail\Mailable::class, function ($mail) use ($user, $admin) {
            $tos = collect($mail->to ?? [])->pluck('address')->all();
            $hasRecipient = in_array($user->email, $tos) || in_array($admin->email, $tos);
            $noIds = !str_contains($mail->subject, '#')
                      && !str_contains($mail->viewData['bodyText'] ?? '', '#');
            return $hasRecipient && $noIds;
        });

        // perform inspection as admin - accept
        $this->actingAs($admin);
        $resp2 = $this->postJson("/api/admin/return-requests/{$reqId}/complete", [
            'condition' => 'Good',
            'disposition' => 'ready_to_deploy',
            'missing_items' => [],
        ]);
        $resp2->assertStatus(200);

        $body = $resp2->json('return_request');
        // inspection now only marks as inspected
        $this->assertEquals('inspected', $body['status']);

        // no mail sent yet because only status change will trigger later

        $asset->refresh();
        // asset still assigned after inspection; unassignment happens later when status updated
        $this->assertEquals($user->id, $asset->Employee_ID, 'Asset remains assigned until status update');

        // now simulate final acceptance via status update
        $resp3 = $this->putJson("/api/return-requests/{$reqId}/status", ['status' => 'accepted']);
        $resp3->assertStatus(200);
        $body2 = $resp3->json('return_request');
        $this->assertEquals('closed', $body2['status']);
        $asset->refresh();
        $this->assertNull($asset->Employee_ID, 'Asset should be unassigned after final acceptance');

        // acceptance email should have been dispatched to requester
        Mail::assertQueued(\Illuminate\Mail\Mailable::class, function ($mail) use ($user) {
            return in_array($user->email, collect($mail->to ?? [])->pluck('address')->all());
        });
    }

    public function test_admin_rejects_return_and_request_remains_pending_with_reason()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $status = Status::create(['Status_Name' => 'Available']);
        $supplier = Supplier::create(["Supplier_Name" => "FooCorp", "Location" => "Here", "Contact" => "none"]);
        $asset = Asset::create([
            'Asset_Name' => 'Another Device',
            'Asset_Category' => 'Desktop',
            'Supplier_ID' => $supplier->id,
            'Status_ID' => $status->id,
            'Employee_ID' => $user->id,
        ]);

        $this->actingAs($user);
        $resp = $this->postJson('/api/return-requests', [
            'asset_id' => $asset->id,
            'sender_condition' => 'good',
        ]);
        $reqId = $resp->json('return_request.id');

        $this->actingAs($admin);
        $resp2 = $this->postJson("/api/admin/return-requests/{$reqId}/complete", [
            'condition' => 'Fair',
            'disposition' => 'non_deployable',
            'missing_items' => [],
            'admin_notes' => 'Screen cracked',
        ]);
        $resp2->assertStatus(200);

        $body = $resp2->json('return_request');
        // after inspection status still inspected
        $this->assertEquals('inspected', $body['status']);
        $this->assertStringContainsString('Screen cracked', $body['notes'] ?? '');

        $asset->refresh();
        $this->assertEquals($user->id, $asset->Employee_ID, 'Asset remains assigned until status update');
    }

    public function test_admin_can_reject_return_after_inspection_with_reason()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $status = Status::create(['Status_Name' => 'Available']);
        $supplier = Supplier::create(["Supplier_Name" => "BarCo", "Location" => "Elsewhere", "Contact" => "none"]);
        $asset = Asset::create([
            'Asset_Name' => 'Device Three',
            'Asset_Category' => 'Tablet',
            'Supplier_ID' => $supplier->id,
            'Status_ID' => $status->id,
            'Employee_ID' => $user->id,
        ]);

        $this->actingAs($user);
        $resp = $this->postJson('/api/return-requests', [
            'asset_id' => $asset->id,
            'sender_condition' => 'good',
        ]);
        $reqId = $resp->json('return_request.id');

        // inspector marks request as inspected (status stays inspected)
        $this->actingAs($admin);
        $this->postJson("/api/admin/return-requests/{$reqId}/complete", [
            'condition' => 'Poor',
            'disposition' => 'non_deployable',
            'missing_items' => [],
        ]);

        // now perform actual rejection with reason via updateStatus
        $reason = 'Not acceptable condition';
        $resp2 = $this->putJson("/api/return-requests/{$reqId}/status", [
            'status' => 'rejected',
            'reason' => $reason,
        ]);
        $resp2->assertStatus(200);
        $body = $resp2->json('return_request');
        $this->assertEquals('rejected', $body['status']);
        $this->assertStringContainsString($reason, $body['notes'] ?? '');

        $asset->refresh();
        $this->assertNull($asset->Employee_ID, 'Asset should become unassigned when status updated');
    }

    public function test_return_request_can_be_submitted_with_items_only()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        // create dummy asset to attach to logs but not use in request
        $status = Status::create(['Status_Name' => 'Available']);
        $supplier = Supplier::create(["Supplier_Name" => "ItemCo", "Location" => "Here", "Contact" => "none"]);
        $asset = Asset::create([
            'Asset_Name' => 'Placeholder',
            'Asset_Category' => 'Misc',
            'Supplier_ID' => $supplier->id,
            'Status_ID' => $status->id,
            'Employee_ID' => $user->id,
        ]);

        // create component and accessory records so the name resolver can look them up
        $comp1 = \App\Models\Accessory::create([
            'name' => 'Comp A',
            'category' => 'Generic',
            'serial_no' => 'C-001',
            'total_qty' => 1,
            'remaining_qty' => 1,
            'price' => 0,
            'type' => 'component',
        ]);
        $acc1 = \App\Models\Accessory::create([
            'name' => 'Acc B',
            'category' => 'Generic',
            'model_number' => 'A-001',
            'total_qty' => 1,
            'remaining_qty' => 1,
            'price' => 0,
        ]);
        // the transfer/return controller just checks that the ids exist, but we
        // also want to verify the API returns the human‑readable names.
        $this->actingAs($user);
        \Illuminate\Support\Facades\Mail::fake();

        $resp = $this->postJson('/api/return-requests', [
            'items' => [
                ['type' => 'component', 'id' => $comp1->id],
                ['type' => 'accessory', 'id' => $acc1->id],
            ],
            'sender_condition' => 'good',
        ]);
        $resp->assertStatus(201);
        $body = $resp->json('return_request');
        $this->assertArrayHasKey('items', $body);
        $this->assertCount(2, $body['items']);
        $this->assertEquals('component', $body['items'][0]['type']);

        Mail::assertQueued(\Illuminate\Mail\Mailable::class, function ($mail) {
            // at least one mail queued (conf or admin)
            return true;
        });

        // returned items should carry a readable name
        $this->assertEquals('component', $body['items'][0]['type']);
        $this->assertArrayHasKey('name', $body['items'][0]);
        $this->assertStringContainsString('Comp A', $body['items'][0]['name']);
        $this->assertEquals('accessory', $body['items'][1]['type']);
        $this->assertStringContainsString('Acc B', $body['items'][1]['name']);

        // verify the list endpoint also includes the new request with items and no asset
        $listResp = $this->getJson('/api/return-requests?pending=1');
        $payload = $listResp->json();
        $rows = $payload['data'] ?? $payload; // pagination vs plain
        $this->assertNotEmpty($rows);
        $found = collect($rows)->firstWhere('id', $body['id']);
        $this->assertNotNull($found, 'Request should appear in list');
        $this->assertArrayHasKey('items', $found);
        $this->assertCount(2, $found['items']);
        $this->assertNull($found['asset'], 'Asset should be null when items-only');

        // admin inspection should still work even when no primary asset
        $reqId = $body['id'];
        $this->actingAs($admin);
        $this->postJson("/api/admin/return-requests/{$reqId}/complete", [
            'condition' => 'Good',
            'disposition' => 'ready_to_deploy',
            'missing_items' => [],
        ])->assertStatus(200);
    }

    public function test_index_endpoint_respects_pending_filter_and_pagination()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $status = Status::create(['Status_Name' => 'Available']);
        $supplier = Supplier::create(["Supplier_Name" => "Test", "Location" => "X", "Contact" => "none"]);

        // create three requests, one will be branded "closed"
        foreach (['A','B','C'] as $letter) {
            $resp = $this->postJson('/api/return-requests', [
                'asset_id' => Asset::create([
                    'Asset_Name' => "Device{$letter}",
                    'Asset_Category' => 'Tool',
                    'Supplier_ID' => $supplier->id,
                    'Status_ID' => $status->id,
                    'Employee_ID' => $admin->id,
                ])->id,
                'sender_condition' => 'good',
            ]);
            $ids[] = $resp->json('return_request.id');
        }

        // mark the first one closed
        $this->putJson("/api/return-requests/{$ids[0]}/status", ['status' => 'accepted']);

        // fetch with pending filter: should return two items and pagination keys
        $result = $this->getJson('/api/return-requests?pending=1&per_page=2')->json();
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
        $this->assertArrayHasKey('links', $result);
        $this->assertNotContains($ids[0], array_column($result['data'], 'id'));
    }
}
