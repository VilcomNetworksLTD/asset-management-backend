<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_ticket_is_prevented_within_thirty_seconds()
    {
        // create a user and act as them
        $user = User::factory()->create();
        $this->actingAs($user);

        // make sure a default status exists, migrations may not seed any when using sqlite
        \App\Models\Status::create(['Status_Name' => 'Pending']);

        $payload = [
            'subject' => 'General issue',
            'description' => 'Something is broken',
            'priority' => 'medium',
        ];

        // first submission should create a ticket
        $response1 = $this->postJson('/api/tickets', $payload);
        $response1->assertStatus(201);
        $ticketId = $response1->json('id');
        $this->assertNotNull($ticketId);

        // second submission with identical data should return the same record
        $response2 = $this->postJson('/api/tickets', $payload);
        $response2->assertStatus(200);
        $this->assertEquals($ticketId, $response2->json('id'));

        // ensure only one ticket exists in database
        $this->assertCount(1, Ticket::all());
    }
}
