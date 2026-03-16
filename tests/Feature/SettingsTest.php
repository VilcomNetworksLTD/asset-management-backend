<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_retrieve_settings()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/settings');
        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }

    public function test_admin_can_update_and_read_back_settings()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // initially there should be no settings saved
        $this->assertEmpty($this->getJson('/api/settings')->json());

        $data = [
            'system_name' => 'Asset Simulator',
            'support_email' => 'help@assets.example',
            'currency' => 'EUR',
            'maintenance_mode' => true,
            'email_alerts' => false,
            'asset_movement_alerts' => true,
            'session_timeout' => 120,
        ];

        $resp = $this->postJson('/api/settings', $data);
        $resp->assertStatus(200)
             ->assertJson(['message' => 'Settings updated successfully!']);

        // a normal user should not be able to change settings.  If
        // maintenance_mode happened to be turned on they may also see a 503
        // (middleware runs before the role check), so accept either.
        $user = User::factory()->create();
        $this->actingAs($user);
        $forbidden = $this->postJson('/api/settings', ['currency' => 'USD']);
        $this->assertTrue(in_array($forbidden->getStatusCode(), [403, 503]));

        // switch back to admin so we can read the values below
        $this->actingAs($admin);

        // confirm database entries exist
        $this->assertDatabaseHas('settings', [
            'key' => 'system_name',
            'value' => 'Asset Simulator'
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'currency',
            'value' => 'EUR'
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'maintenance_mode',
            'value' => '1'   // boolean gets stringified
        ]);

        // fetching now returns our values
        $get = $this->getJson('/api/settings');
        $get->assertStatus(200);
        $settings = $get->json();
        $this->assertEquals('Asset Simulator', $settings['system_name']);
        $this->assertEquals('EUR', $settings['currency']);
        $this->assertTrue($settings['maintenance_mode'] === true);
        $this->assertFalse($settings['email_alerts']);
        $this->assertTrue($settings['asset_movement_alerts']);
        $this->assertIsInt($settings['session_timeout']);
    }

    public function test_maintenance_mode_blocks_non_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $this->postJson('/api/settings', ['maintenance_mode' => true]);

        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);
        $blocked = $this->getJson('/api/user');
        $blocked->assertStatus(503);

        // admin must still be able to access
        $this->actingAs($admin);
        $ok = $this->getJson('/api/user');
        $ok->assertStatus(200);
    }
}
