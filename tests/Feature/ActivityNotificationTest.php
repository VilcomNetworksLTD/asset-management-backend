<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ActivityNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_creation_sends_email_to_admins()
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'boss@example.com']);
        $staff = User::factory()->create(['role' => 'staff', 'email' => 'worker@example.com']);

        ActivityLog::create([
            'asset_id' => null,
            'Employee_ID' => $staff->id,
            'user_name' => $staff->name,
            'action' => 'Test Action',
            'target_type' => 'Demo',
            'target_name' => 'Record',
            'details' => 'some details',
        ]);

        // one email should have been queued, to the admin address
        Mail::assertQueued(\App\Mail\ActivityAlert::class, function ($mail) use ($admin) {
            $hasAdmin = collect($mail->to)->pluck('address')->contains($admin->email);
            $noIds = ! preg_match('/#\d+/', $mail->bodyText);
            return $hasAdmin && $noIds;
        });
    }

    public function test_transfer_verification_generates_activity_log_and_notification()
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'boss@example.com']);
        $sender = User::factory()->create(['role' => 'staff']);
        $receiver = User::factory()->create(['role' => 'staff']);

        // create required records rather than relying on a factory
        $status = \App\Models\Status::create(['Status_Name' => 'Available']);
        $supplier = \App\Models\Supplier::create(["Supplier_Name" => "TestCo", "Location" => "Nowhere", "Contact" => "none"]);
        $asset = \App\Models\Asset::create([
            'Asset_Name' => 'Device X',
            'Asset_Category' => 'Gadget',
            'Supplier_ID' => $supplier->id,
            'Status_ID' => $status->id,
            'Employee_ID' => $sender->id,
        ]);

        $transfer = \App\Models\Transfer::create([
            'Asset_ID' => $asset->id,
            'Employee_ID' => $receiver->id,
            'Sender_ID' => $sender->id,
            'Receiver_ID' => $receiver->id,
            'Status_ID' => $status->id,
            'Transfer_Date' => now(),
            'Type' => 'transfer',
            'Workflow_Status' => 'pending_verification',
        ]);

        $this->actingAs($receiver);
        $response = $this->postJson("/api/assignments/{$transfer->id}/verify", [
            'status' => 'accepted',
        ]);
        $errors = $response->json('errors');
        if (!empty($errors)) {
            $this->fail('validation errors: '.json_encode($errors));
        }
        $this->assertSame(200, $response->status(), 'full response: '.$response->getContent());

        // verify an activity log exists
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Transfer Verified',
        ]);


        // and that an ActivityAlert was sent (recipient checks are covered in
        // the previous test so deep validation isn't required here)
        Mail::assertQueued(\App\Mail\ActivityAlert::class, function ($mail) {
            return ! preg_match('/#\d+/', $mail->bodyText);
        });
    }

    public function test_inbound_verification_handles_missing_asset_gracefully()
    {
        Mail::fake();

        $sender = User::factory()->create(['role' => 'staff']);
        $receiver = User::factory()->create(['role' => 'staff']);
        $status = \App\Models\Status::create(['Status_Name' => 'Available']);

        // create a transfer record with no asset_id to simulate mixed items
        $transfer = \App\Models\Transfer::create([
            'Asset_ID' => null,
            'Employee_ID' => $receiver->id,
            'Sender_ID' => $sender->id,
            'Receiver_ID' => $receiver->id,
            'Status_ID' => $status->id,
            'Transfer_Date' => now(),
            'Type' => 'assignment',
            'Workflow_Status' => 'pending_verification',
        ]);

        $this->actingAs($receiver);
        $response = $this->postJson("/api/assignments/{$transfer->id}/verify", [
            'status' => 'accepted',
        ]);
        $this->assertSame(200, $response->status());

        // transfer should be marked deployed even though asset was null
        $this->assertDatabaseHas('transfers', [
            'id' => $transfer->id,
            'Workflow_Status' => 'deployed',
        ]);

        // activity log should still be created and target should indicate mixed items
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Transfer Verified',
            'target_type' => 'Mixed Items',
        ]);
    }
}
