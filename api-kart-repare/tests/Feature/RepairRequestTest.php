<?php

namespace Tests\Feature;

use App\Models\RepairRequest;
use App\Models\User;
use App\Models\Kart;
use App\Models\RequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RepairRequestTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function authenticated_user_can_view_their_repair_requests()
    {
        $user = User::factory()->create(['role' => 'client']);
        $kart = Kart::factory()->create();
        $status = RequestStatus::factory()->create();

        RepairRequest::factory()->create([
            'created_by' => $user->id,
            'kart_id' => $kart->id,
            'status_id' => $status->id
        ]);
        RepairRequest::factory()->create(); // Autre utilisateur

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/repair-requests');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    /** @test */
    public function unauthenticated_user_cannot_view_repair_requests()
    {
        RepairRequest::factory()->count(3)->create();

        $response = $this->getJson('/api/repair-requests');

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.'
                 ]);
    }

    /** @test */
    public function user_can_create_repair_request()
    {
        $user = User::factory()->create(['role' => 'client']);
        $kart = Kart::factory()->create();
        $status = RequestStatus::factory()->create();

        Sanctum::actingAs($user);

        $requestData = [
            'kart_id' => $kart->id,
            'title' => 'Test Repair Request',
            'description' => 'Test description for repair',
            'status_id' => $status->id,
            'priority' => 'medium',
            'estimated_cost' => 150.00,
            'estimated_completion' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->postJson('/api/repair-requests', $requestData);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'title' => 'Test Repair Request',
                     'priority' => 'medium',
                     'estimated_cost' => '150.00',
                 ]);

        $this->assertDatabaseHas('repair_requests', [
            'title' => 'Test Repair Request',
            'created_by' => $user->id,
            'kart_id' => $kart->id,
        ]);
    }

    /** @test */
    public function user_can_view_single_repair_request_they_created()
    {
        $user = User::factory()->create(['role' => 'client']);
        $repairRequest = RepairRequest::factory()->create(['created_by' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/repair-requests/{$repairRequest->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $repairRequest->id,
                     'title' => $repairRequest->title,
                 ]);
    }

    /** @test */
    public function user_cannot_view_repair_request_of_another_user()
    {
        $user = User::factory()->create(['role' => 'client']);
        $otherUser = User::factory()->create(['role' => 'client']);
        $repairRequest = RepairRequest::factory()->create(['created_by' => $otherUser->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/repair-requests/{$repairRequest->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_all_repair_requests()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        RepairRequest::factory()->count(5)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/repair-requests');

        $response->assertStatus(200);
        $this->assertEquals(5, count($response->json('data')));
    }

    /** @test */
    public function user_can_update_their_pending_repair_request()
    {
        $user = User::factory()->create(['role' => 'client']);
        $repairRequest = RepairRequest::factory()->create([
            'created_by' => $user->id,
            'title' => 'Original Title',
            'started_at' => null,
        ]);

        Sanctum::actingAs($user);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/repair-requests/{$repairRequest->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'title' => 'Updated Title',
                 ]);

        $this->assertDatabaseHas('repair_requests', [
            'id' => $repairRequest->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function user_cannot_update_started_repair_request()
    {
        $user = User::factory()->create(['role' => 'client']);
        $repairRequest = RepairRequest::factory()->create([
            'created_by' => $user->id,
            'started_at' => now()->subDays(2),
        ]);

        Sanctum::actingAs($user);

        $updateData = ['title' => 'Updated Title'];

        $response = $this->putJson("/api/repair-requests/{$repairRequest->id}", $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_pending_repair_request()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $repairRequest = RepairRequest::factory()->create(['started_at' => null]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/repair-requests/{$repairRequest->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('repair_requests', ['id' => $repairRequest->id]);
    }

    /** @test */
    public function admin_cannot_delete_started_repair_request()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $repairRequest = RepairRequest::factory()->create(['started_at' => now()->subDays(2)]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/repair-requests/{$repairRequest->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function repair_request_creation_validation_errors()
    {
        $user = User::factory()->create(['role' => 'client']);

        Sanctum::actingAs($user);

        $invalidData = [
            'kart_id' => 999, // Non-existent kart
            'title' => '', // Required
            'priority' => 'invalid', // Must be low, medium, high
            'estimated_cost' => -50, // Must be positive
        ];

        $response = $this->postJson('/api/repair-requests', $invalidData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'kart_id',
                     'title',
                     'priority',
                     'estimated_cost',
                 ]);
    }

    /** @test */
    public function admin_can_start_repair_request()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $repairRequest = RepairRequest::factory()->create(['started_at' => null]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/repair-requests/{$repairRequest->id}/start");

        $response->assertStatus(200);

        $repairRequest->refresh();
        $this->assertNotNull($repairRequest->started_at);
    }

    /** @test */
    public function admin_can_complete_repair_request()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $repairRequest = RepairRequest::factory()->create([
            'started_at' => now()->subDays(2),
            'completed_at' => null,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/repair-requests/{$repairRequest->id}/complete", [
            'actual_cost' => 180.50
        ]);

        $response->assertStatus(200);

        $repairRequest->refresh();
        $this->assertNotNull($repairRequest->completed_at);
        $this->assertEquals(180.50, $repairRequest->actual_cost);
    }

    /** @test */
    public function admin_can_assign_mechanic_to_repair_request()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mechanic = User::factory()->create(['role' => 'bureau_staff']);
        $repairRequest = RepairRequest::factory()->create(['assigned_to' => null]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/repair-requests/{$repairRequest->id}/assign", [
            'mechanic_id' => $mechanic->id
        ]);

        $response->assertStatus(200);

        $repairRequest->refresh();
        $this->assertEquals($mechanic->id, $repairRequest->assigned_to);
    }

    /** @test */
    public function admin_can_view_repair_requests_statistics()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        RepairRequest::factory()->count(5)->create(['priority' => 'high']);
        RepairRequest::factory()->count(3)->completed()->create();
        RepairRequest::factory()->count(2)->overdue()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/repair-requests/statistics');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total',
                     'active',
                     'completed',
                     'overdue',
                     'by_priority',
                     'costs',
                     'timeline',
                     'deleted',
                 ]);
    }

    /** @test */
    public function can_filter_repair_requests_by_priority()
    {
        $user = User::factory()->create(['role' => 'admin']);

        RepairRequest::factory()->count(3)->create(['priority' => 'high']);
        RepairRequest::factory()->count(2)->create(['priority' => 'medium']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/repair-requests?priority=high');

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
    }

    /** @test */
    public function can_filter_repair_requests_by_completion_status()
    {
        $user = User::factory()->create(['role' => 'admin']);

        RepairRequest::factory()->count(3)->completed()->create();
        RepairRequest::factory()->count(2)->active()->create();

        Sanctum::actingAs($user);

        // Test completed requests
        $response = $this->getJson('/api/repair-requests?is_completed=1');
        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));

        // Test active requests
        $response = $this->getJson('/api/repair-requests?is_completed=0');
        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
    }

    /** @test */
    public function can_search_repair_requests_by_title()
    {
        $user = User::factory()->create(['role' => 'admin']);

        RepairRequest::factory()->create(['title' => 'Engine repair needed']);
        RepairRequest::factory()->create(['title' => 'Brake system check']);
        RepairRequest::factory()->create(['title' => 'Tire replacement']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/repair-requests?search=engine');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, count($data));
        $this->assertStringContainsString('Engine', $data[0]['title']);
    }

    /** @test */
    public function repair_request_has_correct_computed_attributes()
    {
        $repairRequest = RepairRequest::factory()->create([
            'priority' => 'high',
            'estimated_cost' => 100.00,
            'actual_cost' => 120.50,
            'started_at' => now()->subDays(5),
            'completed_at' => now()->subDays(1),
        ]);

        $this->assertEquals('Haute', $repairRequest->priority_display);
        $this->assertEquals('#dc3545', $repairRequest->priority_color);
        $this->assertEquals(20.50, $repairRequest->cost_variance);
        $this->assertTrue($repairRequest->isCompleted());
        $this->assertTrue($repairRequest->isStarted());
        $this->assertEquals(4, $repairRequest->duration_days);
    }

    /** @test */
    public function repair_request_scopes_work_correctly()
    {
        RepairRequest::factory()->create(['priority' => 'high']);
        RepairRequest::factory()->completed()->create();
        RepairRequest::factory()->overdue()->create();
        RepairRequest::factory()->active()->create();

        $this->assertEquals(1, RepairRequest::withPriority('high')->count());
        $this->assertEquals(1, RepairRequest::completed()->count());
        $this->assertEquals(1, RepairRequest::overdue()->count());
        $this->assertEquals(1, RepairRequest::active()->count());
    }
}
