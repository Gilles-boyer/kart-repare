<?php

namespace Tests\Feature;

use App\Models\Pilot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PilotTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function admin_can_view_all_pilots()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);
        Pilot::factory()->count(3)->forClient($client)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/pilots');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'client_id',
                             'full_name',
                             'first_name',
                             'last_name',
                             'date_of_birth',
                             'is_minor',
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function client_can_only_view_own_pilots()
    {
        $client1 = User::factory()->create(['role' => 'client']);
        $client2 = User::factory()->create(['role' => 'client']);

        Pilot::factory()->count(2)->forClient($client1)->create();
        Pilot::factory()->count(3)->forClient($client2)->create();

        Sanctum::actingAs($client1);

        $response = $this->getJson('/api/pilots');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
    }

    /** @test */
    public function unauthenticated_user_cannot_view_pilots()
    {
        Pilot::factory()->count(3)->create();

        $response = $this->getJson('/api/pilots');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_create_pilot_for_any_client()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);

        Sanctum::actingAs($admin);

        $pilotData = [
            'client_id' => $client->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-05-15',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '1234567890',
            'is_minor' => false,
        ];

        $response = $this->postJson('/api/pilots', $pilotData);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'first_name' => 'John',
                     'last_name' => 'Doe',
                 ]);

        $this->assertDatabaseHas('pilots', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'client_id' => $client->id,
        ]);
    }

    /** @test */
    public function client_can_create_pilot_for_themselves()
    {
        $client = User::factory()->create(['role' => 'client']);

        Sanctum::actingAs($client);

        $pilotData = [
            'client_id' => $client->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-05-15',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '1234567890',
            'is_minor' => false,
        ];

        $response = $this->postJson('/api/pilots', $pilotData);

        $response->assertStatus(201);
    }

    /** @test */
    public function client_cannot_create_pilot_for_another_client()
    {
        $client1 = User::factory()->create(['role' => 'client']);
        $client2 = User::factory()->create(['role' => 'client']);

        Sanctum::actingAs($client1);

        $pilotData = [
            'client_id' => $client2->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-05-15',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '1234567890',
            'is_minor' => false,
        ];

        $response = $this->postJson('/api/pilots', $pilotData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_single_pilot()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pilot = Pilot::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/pilots/{$pilot->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $pilot->id,
                     'first_name' => $pilot->first_name,
                 ]);
    }

    /** @test */
    public function client_can_view_own_pilot()
    {
        $client = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client)->create();

        Sanctum::actingAs($client);

        $response = $this->getJson("/api/pilots/{$pilot->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_view_other_clients_pilot()
    {
        $client1 = User::factory()->create(['role' => 'client']);
        $client2 = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client2)->create();

        Sanctum::actingAs($client1);

        $response = $this->getJson("/api/pilots/{$pilot->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_pilot()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pilot = Pilot::factory()->create();

        Sanctum::actingAs($admin);

        $updateData = [
            'first_name' => 'UpdatedName',
            'phone' => '9876543210',
        ];

        $response = $this->putJson("/api/pilots/{$pilot->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'first_name' => 'UpdatedName',
                 ]);

        $this->assertDatabaseHas('pilots', [
            'id' => $pilot->id,
            'first_name' => 'UpdatedName',
            'phone' => '9876543210',
        ]);
    }

    /** @test */
    public function client_can_update_own_pilot()
    {
        $client = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client)->create();

        Sanctum::actingAs($client);

        $updateData = [
            'first_name' => 'UpdatedName',
        ];

        $response = $this->putJson("/api/pilots/{$pilot->id}", $updateData);

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_delete_pilot()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pilot = Pilot::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/pilots/{$pilot->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('pilots', ['id' => $pilot->id]);
    }

    /** @test */
    public function client_can_delete_own_pilot()
    {
        $client = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client)->create();

        Sanctum::actingAs($client);

        $response = $this->deleteJson("/api/pilots/{$pilot->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function pilot_creation_validation_errors()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $invalidData = [
            'client_id' => 999, // Non-existent client
            'first_name' => '',
            'date_of_birth' => 'invalid-date',
            'size_shoes' => 99, // Invalid size
        ];

        $response = $this->postJson('/api/pilots', $invalidData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'client_id',
                     'first_name',
                     'date_of_birth',
                     'size_shoes',
                     'last_name',
                     'emergency_contact_name',
                     'emergency_contact_phone',
                 ]);
    }

    /** @test */
    public function admin_can_view_pilots_statistics()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);

        Pilot::factory()->count(5)->forClient($client)->create(['is_minor' => false]);
        Pilot::factory()->count(3)->forClient($client)->create(['is_minor' => true]);
        Pilot::factory()->count(2)->forClient($client)->create(['email' => 'test@example.com']);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/pilots/statistics');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total',
                     'minors',
                     'adults',
                     'with_email',
                     'with_phone',
                     'deleted',
                 ]);
    }

    /** @test */
    public function client_can_view_own_pilots_statistics()
    {
        $client = User::factory()->create(['role' => 'client']);
        $anotherClient = User::factory()->create(['role' => 'client']);

        Pilot::factory()->count(3)->forClient($client)->create();
        Pilot::factory()->count(5)->forClient($anotherClient)->create(); // Shouldn't be counted

        Sanctum::actingAs($client);

        $response = $this->getJson('/api/pilots/statistics');

        $response->assertStatus(200);
        $stats = $response->json();

        $this->assertEquals(3, $stats['total']); // Only own pilots
    }
}
