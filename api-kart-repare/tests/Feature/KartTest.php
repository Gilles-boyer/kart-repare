<?php

namespace Tests\Feature;

use App\Models\Kart;
use App\Models\Pilot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KartTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function admin_can_view_all_karts()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client)->create();
        Kart::factory()->count(3)->forPilot($pilot)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/karts');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'pilot_id',
                             'brand',
                             'model',
                             'chassis_number',
                             'year',
                             'is_active',
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function client_can_only_view_own_karts()
    {
        $client1 = User::factory()->create(['role' => 'client']);
        $client2 = User::factory()->create(['role' => 'client']);

        $pilot1 = Pilot::factory()->forClient($client1)->create();
        $pilot2 = Pilot::factory()->forClient($client2)->create();

        Kart::factory()->count(2)->forPilot($pilot1)->create();
        Kart::factory()->count(3)->forPilot($pilot2)->create();

        Sanctum::actingAs($client1);

        $response = $this->getJson('/api/karts');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
    }

    /** @test */
    public function unauthenticated_user_cannot_view_karts()
    {
        $pilot = Pilot::factory()->create();
        Kart::factory()->count(3)->forPilot($pilot)->create();

        $response = $this->getJson('/api/karts');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_create_kart_for_any_pilot()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client)->create();

        Sanctum::actingAs($admin);

        $kartData = [
            'pilot_id' => $pilot->id,
            'brand' => 'Tony Kart',
            'model' => 'Racer 401S',
            'chassis_number' => 'TK2024001',
            'year' => 2024,
            'engine_type' => '2T',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/karts', $kartData);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'brand' => 'Tony Kart',
                     'model' => 'Racer 401S',
                     'chassis_number' => 'TK2024001',
                 ]);

        $this->assertDatabaseHas('karts', [
            'brand' => 'Tony Kart',
            'chassis_number' => 'TK2024001',
            'pilot_id' => $pilot->id,
        ]);
    }

    /** @test */
    public function client_can_create_kart_for_own_pilot()
    {
        $client = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client)->create();

        Sanctum::actingAs($client);

        $kartData = [
            'pilot_id' => $pilot->id,
            'brand' => 'CRG',
            'model' => 'Road Rebel',
            'chassis_number' => 'CRG2024001',
            'year' => 2024,
            'engine_type' => '4T',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/karts', $kartData);

        $response->assertStatus(201);
    }

    /** @test */
    public function client_cannot_create_kart_for_another_clients_pilot()
    {
        $client1 = User::factory()->create(['role' => 'client']);
        $client2 = User::factory()->create(['role' => 'client']);
        $pilot2 = Pilot::factory()->forClient($client2)->create();

        Sanctum::actingAs($client1);

        $kartData = [
            'pilot_id' => $pilot2->id,
            'brand' => 'Birel ART',
            'model' => 'C28',
            'chassis_number' => 'BA2024001',
            'year' => 2024,
            'engine_type' => '2T',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/karts', $kartData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_single_kart()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pilot = Pilot::factory()->create();
        $kart = Kart::factory()->forPilot($pilot)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/karts/{$kart->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $kart->id,
                     'brand' => $kart->brand,
                 ]);
    }

    /** @test */
    public function client_can_view_own_kart()
    {
        $client = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client)->create();
        $kart = Kart::factory()->forPilot($pilot)->create();

        Sanctum::actingAs($client);

        $response = $this->getJson("/api/karts/{$kart->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_view_other_clients_kart()
    {
        $client1 = User::factory()->create(['role' => 'client']);
        $client2 = User::factory()->create(['role' => 'client']);
        $pilot2 = Pilot::factory()->forClient($client2)->create();
        $kart = Kart::factory()->forPilot($pilot2)->create();

        Sanctum::actingAs($client1);

        $response = $this->getJson("/api/karts/{$kart->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_kart()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pilot = Pilot::factory()->create();
        $kart = Kart::factory()->forPilot($pilot)->create();

        Sanctum::actingAs($admin);

        $updateData = [
            'brand' => 'Updated Brand',
            'year' => 2023,
            'is_active' => false,
        ];

        $response = $this->putJson("/api/karts/{$kart->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'brand' => 'Updated Brand',
                     'year' => 2023,
                     'is_active' => false,
                 ]);

        $this->assertDatabaseHas('karts', [
            'id' => $kart->id,
            'brand' => 'Updated Brand',
            'year' => 2023,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function client_can_update_own_kart()
    {
        $client = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client)->create();
        $kart = Kart::factory()->forPilot($pilot)->create();

        Sanctum::actingAs($client);

        $updateData = [
            'brand' => 'Updated Brand',
        ];

        $response = $this->putJson("/api/karts/{$kart->id}", $updateData);

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_delete_kart()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pilot = Pilot::factory()->create();
        $kart = Kart::factory()->forPilot($pilot)->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/karts/{$kart->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('karts', ['id' => $kart->id]);
    }

    /** @test */
    public function client_can_delete_own_kart()
    {
        $client = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client)->create();
        $kart = Kart::factory()->forPilot($pilot)->create();

        Sanctum::actingAs($client);

        $response = $this->deleteJson("/api/karts/{$kart->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function kart_creation_validation_errors()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $invalidData = [
            'pilot_id' => 999, // Non-existent pilot
            'brand' => '',
            'model' => '',
            'chassis_number' => '',
            'year' => 1900, // Too old
            'engine_type' => 'INVALID',
        ];

        $response = $this->postJson('/api/karts', $invalidData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'pilot_id',
                     'brand',
                     'model',
                     'chassis_number',
                     'year',
                     'engine_type',
                 ]);
    }

    /** @test */
    public function chassis_number_must_be_unique()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pilot1 = Pilot::factory()->create();
        $pilot2 = Pilot::factory()->create();

        // Create first kart with a chassis number
        $kart1 = Kart::factory()->forPilot($pilot1)->create(['chassis_number' => 'UNIQUE123']);

        Sanctum::actingAs($admin);

        // Try to create second kart with same chassis number
        $kartData = [
            'pilot_id' => $pilot2->id,
            'brand' => 'Tony Kart',
            'model' => 'Racer 401S',
            'chassis_number' => 'UNIQUE123', // Duplicate
            'year' => 2024,
            'engine_type' => '2T',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/karts', $kartData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['chassis_number']);
    }

    /** @test */
    public function admin_can_view_karts_statistics()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client)->create();

        Kart::factory()->count(5)->forPilot($pilot)->create(['is_active' => true]);
        Kart::factory()->count(3)->forPilot($pilot)->create(['is_active' => false]);
        Kart::factory()->count(2)->forPilot($pilot)->create(['engine_type' => '2T']);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/karts/statistics');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total',
                     'active',
                     'inactive',
                     'by_engine_type',
                     'by_brand',
                     'vintage',
                     'modern',
                     'deleted',
                 ]);
    }

    /** @test */
    public function client_can_view_own_karts_statistics()
    {
        $client = User::factory()->create(['role' => 'client']);
        $anotherClient = User::factory()->create(['role' => 'client']);

        $pilot1 = Pilot::factory()->forClient($client)->create();
        $pilot2 = Pilot::factory()->forClient($anotherClient)->create();

        Kart::factory()->count(3)->forPilot($pilot1)->create();
        Kart::factory()->count(5)->forPilot($pilot2)->create(); // Shouldn't be counted

        Sanctum::actingAs($client);

        $response = $this->getJson('/api/karts/statistics');

        $response->assertStatus(200);
        $stats = $response->json();

        $this->assertEquals(3, $stats['total']); // Only own karts
    }

    /** @test */
    public function kart_has_full_identification_attribute()
    {
        $pilot = Pilot::factory()->create();
        $kart = Kart::factory()->forPilot($pilot)->create([
            'brand' => 'Tony Kart',
            'model' => 'Racer 401S',
            'chassis_number' => 'TK2024001'
        ]);

        $this->assertEquals(
            'Tony Kart Racer 401S (TK2024001)',
            $kart->full_identification
        );
    }

    /** @test */
    public function kart_scopes_work_correctly()
    {
        $pilot = Pilot::factory()->create();
        Kart::factory()->forPilot($pilot)->create(['is_active' => true]);
        Kart::factory()->forPilot($pilot)->create(['is_active' => false]);

        $this->assertEquals(1, Kart::active()->count());
        $this->assertEquals(1, Kart::inactive()->count());
    }
}
