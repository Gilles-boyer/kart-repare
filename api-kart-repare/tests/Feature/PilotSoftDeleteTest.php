<?php

namespace Tests\Feature;

use App\Models\Pilot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PilotSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function admin_can_view_trashed_pilots()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);

        $pilot = Pilot::factory()->forClient($client)->create();
        $pilot->delete(); // Soft delete

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/pilots/trashed');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment([
                     'id' => $pilot->id,
                 ]);
    }

    /** @test */
    public function client_can_view_own_trashed_pilots()
    {
        $client = User::factory()->create(['role' => 'client']);
        $anotherClient = User::factory()->create(['role' => 'client']);

        $pilot1 = Pilot::factory()->forClient($client)->create();
        $pilot2 = Pilot::factory()->forClient($anotherClient)->create();

        $pilot1->delete();
        $pilot2->delete();

        Sanctum::actingAs($client);

        $response = $this->getJson('/api/pilots/trashed');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data') // Only own trashed pilot
                 ->assertJsonFragment([
                     'id' => $pilot1->id,
                 ]);
    }

    /** @test */
    public function client_cannot_view_other_clients_trashed_pilots()
    {
        $client1 = User::factory()->create(['role' => 'client']);
        $client2 = User::factory()->create(['role' => 'client']);

        $pilot = Pilot::factory()->forClient($client2)->create();
        $pilot->delete();

        Sanctum::actingAs($client1);

        $response = $this->getJson('/api/pilots/trashed');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data'); // No trashed pilots for client1
    }

    /** @test */
    public function admin_can_restore_pilot()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pilot = Pilot::factory()->create();
        $pilot->delete();

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/pilots/{$pilot->id}/restore");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $pilot->id,
                 ]);

        $this->assertDatabaseHas('pilots', [
            'id' => $pilot->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function client_can_restore_own_pilot()
    {
        $client = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client)->create();
        $pilot->delete();

        Sanctum::actingAs($client);

        $response = $this->patchJson("/api/pilots/{$pilot->id}/restore");

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_restore_other_clients_pilot()
    {
        $client1 = User::factory()->create(['role' => 'client']);
        $client2 = User::factory()->create(['role' => 'client']);

        $pilot = Pilot::factory()->forClient($client2)->create();
        $pilot->delete();

        Sanctum::actingAs($client1);

        $response = $this->patchJson("/api/pilots/{$pilot->id}/restore");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_force_delete_pilot()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pilot = Pilot::factory()->create();
        $pilot->delete();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/pilots/{$pilot->id}/force-delete");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('pilots', [
            'id' => $pilot->id,
        ]);
    }

    /** @test */
    public function bureau_staff_can_force_delete_pilot()
    {
        $bureauStaff = User::factory()->create(['role' => 'bureau_staff']);
        $pilot = Pilot::factory()->create();
        $pilot->delete();

        Sanctum::actingAs($bureauStaff);

        $response = $this->deleteJson("/api/pilots/{$pilot->id}/force-delete");

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_force_delete_pilot()
    {
        $client = User::factory()->create(['role' => 'client']);
        $pilot = Pilot::factory()->forClient($client)->create();
        $pilot->delete();

        Sanctum::actingAs($client);

        $response = $this->deleteJson("/api/pilots/{$pilot->id}/force-delete");

        $response->assertStatus(403);
    }

    /** @test */
    public function pilot_deletion_is_soft_delete()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pilot = Pilot::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/pilots/{$pilot->id}");

        $response->assertStatus(200);

        // Pilot should be soft deleted
        $this->assertSoftDeleted('pilots', [
            'id' => $pilot->id,
        ]);

        // Should not appear in normal queries
        $this->assertEquals(0, Pilot::count());

        // But should appear in trashed queries
        $this->assertEquals(1, Pilot::onlyTrashed()->count());
    }

    /** @test */
    public function statistics_include_deleted_pilots()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);

        // Create some pilots
        Pilot::factory()->count(5)->forClient($client)->create();

        // Delete some pilots
        $pilotToDelete = Pilot::factory()->forClient($client)->create();
        $pilotToDelete->delete();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/pilots/statistics');

        $response->assertStatus(200);
        $stats = $response->json();

        $this->assertEquals(5, $stats['total']); // Active pilots
        $this->assertEquals(1, $stats['deleted']); // Deleted pilots
    }

    /** @test */
    public function can_search_trashed_pilots()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $pilot1 = Pilot::factory()->create(['first_name' => 'Test_Pilot_Search_John', 'last_name' => 'Doe']);
        $pilot2 = Pilot::factory()->create(['first_name' => 'Jane', 'last_name' => 'Test_Pilot_Search_Smith']);
        $pilot3 = Pilot::factory()->create(['first_name' => 'Bob', 'last_name' => 'Wilson']);

        $pilot1->delete();
        $pilot2->delete();
        $pilot3->delete();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/pilots/trashed?search=Test_Pilot_Search');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data'); // Should find pilot1 and pilot2
    }
}
