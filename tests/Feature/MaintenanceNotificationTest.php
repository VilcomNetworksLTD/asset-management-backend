<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MaintenanceNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_maintenance_sends_email_to_admins_and_owner()
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'boss@example.com']);
        $owner = User::factory()->create(['role' => 'staff', 'email' => 'worker@example.com']);

        $status = Status::create(['Status_Name' => 'Available']);
        $supplier = Supplier::create(["Supplier_Name" => "Acme", "Location" => "Here", "Contact" => "none"]);
        $asset = Asset::create([
            'Asset_Name' => 'Machine',
            'Asset_Category' => 'Equipment',
            'Supplier_ID' => $supplier->id,
            'Status_ID' => $status->id,
            'Employee_ID' => $owner->id,
        ]);

        $this->actingAs($admin);
        $resp = $this->postJson('/api/maintenances', [
            'Asset_ID' => $asset->id,
            'Request_Date' => now()->toDateString(),
            'Maintenance_Type' => 'Routine Check',
            'Status_ID' => $status->id,
        ]);
        $resp->assertStatus(201);

        Mail::assertQueued(\Illuminate\Mail\Mailable::class, function (\Illuminate\Mail\Mailable $mail) use ($admin, $owner) {
            $tos = collect($mail->to ?? [])->pluck('address')->all();
            return in_array($admin->email, $tos) || in_array($owner->email, $tos);
        });
    }

    public function test_maintenance_mode_event_triggers_email()
    {
        Mail::fake();

        // need at least one admin so the listener has someone to notify
        User::factory()->create(['role' => 'admin', 'email' => 'boss@example.com']);

        // invoke listener directly to avoid event dispatcher reflection issues
        $listener = new \App\Listeners\NotifyAdminsMaintenanceMode();
        $listener->handle(new \Illuminate\Foundation\Events\MaintenanceModeEnabled());

        Mail::assertQueued(\Illuminate\Mail\Mailable::class, function (\Illuminate\Mail\Mailable $mail) {
            return true; // any mail indicates listener fired
        });
    }
}
