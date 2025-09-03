<?php

namespace Tests\Feature;

use App\Models\RequestStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestStatusTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function authenticated_user_can_view_all_request_statuses()
    {
        $user = User::factory()->create(['role' => 'client']);
        RequestStatus::factory()->count(3)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/request-statuses');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'hex_color',
                             'is_final',
                             'display_name',
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_view_request_statuses()
    {
        RequestStatus::factory()->count(3)->create();

        $response = $this->getJson('/api/request-statuses');

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.'
                 ]);
    }

    /** @test */
    public function admin_can_create_request_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $statusData = [
            'name' => 'Test Status',
            'hex_color' => '#FF0000',
            'is_final' => false,
        ];

        $response = $this->postJson('/api/request-statuses', $statusData);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'name' => 'Test Status',
                     'hex_color' => '#FF0000',
                     'is_final' => false,
                 ]);

        $this->assertDatabaseHas('request_statuses', [
            'name' => 'Test Status',
            'hex_color' => '#FF0000',
        ]);
    }

    /** @test */
    public function bureau_staff_can_create_request_status()
    {
        $bureauStaff = User::factory()->create(['role' => 'bureau_staff']);

        Sanctum::actingAs($bureauStaff);

        $statusData = [
            'name' => 'Bureau Status',
            'hex_color' => '#00FF00',
            'is_final' => true,
        ];

        $response = $this->postJson('/api/request-statuses', $statusData);

        $response->assertStatus(201);
    }

    /** @test */
    public function client_cannot_create_request_status()
    {
        $client = User::factory()->create(['role' => 'client']);

        Sanctum::actingAs($client);

        $statusData = [
            'name' => 'Client Status',
            'hex_color' => '#0000FF',
            'is_final' => false,
        ];

        $response = $this->postJson('/api/request-statuses', $statusData);

        $response->assertStatus(403);
    }

    /** @test */
    public function authenticated_user_can_view_single_request_status()
    {
        $user = User::factory()->create(['role' => 'client']);
        $status = RequestStatus::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/request-statuses/{$status->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $status->id,
                     'name' => $status->name,
                 ]);
    }

    /** @test */
    public function admin_can_update_request_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $status = RequestStatus::factory()->create();

        Sanctum::actingAs($admin);

        $updateData = [
            'name' => 'Updated Status',
            'hex_color' => '#FFFF00',
        ];

        $response = $this->putJson("/api/request-statuses/{$status->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'name' => 'Updated Status',
                     'hex_color' => '#FFFF00',
                 ]);

        $this->assertDatabaseHas('request_statuses', [
            'id' => $status->id,
            'name' => 'Updated Status',
            'hex_color' => '#FFFF00',
        ]);
    }

    /** @test */
    public function client_cannot_update_request_status()
    {
        $client = User::factory()->create(['role' => 'client']);
        $status = RequestStatus::factory()->create();

        Sanctum::actingAs($client);

        $updateData = [
            'name' => 'Unauthorized Update',
        ];

        $response = $this->putJson("/api/request-statuses/{$status->id}", $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_request_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $status = RequestStatus::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/request-statuses/{$status->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('request_statuses', ['id' => $status->id]);
    }

    /** @test */
    public function client_cannot_delete_request_status()
    {
        $client = User::factory()->create(['role' => 'client']);
        $status = RequestStatus::factory()->create();

        Sanctum::actingAs($client);

        $response = $this->deleteJson("/api/request-statuses/{$status->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function request_status_creation_validation_errors()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $invalidData = [
            'name' => '', // Required
            'hex_color' => 'invalid-color', // Must be hex format
        ];

        $response = $this->postJson('/api/request-statuses', $invalidData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'name',
                     'hex_color',
                 ]);
    }

    /** @test */
    public function request_status_name_must_be_unique()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existingStatus = RequestStatus::factory()->create(['name' => 'Unique Status']);

        Sanctum::actingAs($admin);

        $statusData = [
            'name' => 'Unique Status', // Duplicate name
            'hex_color' => '#FF0000',
            'is_final' => false,
        ];

        $response = $this->postJson('/api/request-statuses', $statusData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function hex_color_is_automatically_formatted()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $statusData = [
            'name' => 'Color Test',
            'hex_color' => 'ff0000', // Without # prefix and lowercase
            'is_final' => false,
        ];

        $response = $this->postJson('/api/request-statuses', $statusData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('request_statuses', [
            'name' => 'Color Test',
            'hex_color' => '#FF0000', // Should be formatted
        ]);
    }

    /** @test */
    public function admin_can_view_request_statuses_statistics()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Créer des statuses avec des noms uniques
        for ($i = 1; $i <= 5; $i++) {
            RequestStatus::factory()->create([
                'is_final' => false,
                'name' => "Status Non Final {$i}"
            ]);
        }

        for ($i = 1; $i <= 3; $i++) {
            RequestStatus::factory()->create([
                'is_final' => true,
                'name' => "Status Final {$i}"
            ]);
        }

        for ($i = 1; $i <= 2; $i++) {
            RequestStatus::factory()->create([
                'hex_color' => '#FF0000',
                'name' => "Status Rouge {$i}"
            ]);
        }

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/request-statuses/statistics');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total',
                     'final',
                     'not_final',
                     'by_color',
                     'most_common_colors',
                     'deleted',
                 ]);
    }

    /** @test */
    public function can_filter_request_statuses_by_final_status()
    {
        $user = User::factory()->create(['role' => 'client']);

        // Créer des statuses avec des noms uniques
        for ($i = 1; $i <= 3; $i++) {
            RequestStatus::factory()->create([
                'is_final' => true,
                'name' => "Status Final Filter {$i}"
            ]);
        }

        for ($i = 1; $i <= 2; $i++) {
            RequestStatus::factory()->create([
                'is_final' => false,
                'name' => "Status Non Final Filter {$i}"
            ]);
        }

        Sanctum::actingAs($user);

        // Test final statuses
        $response = $this->getJson('/api/request-statuses?is_final=1');
        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));

        // Test non-final statuses
        $response = $this->getJson('/api/request-statuses?is_final=0');
        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
    }

    /** @test */
    public function can_search_request_statuses_by_name()
    {
        $user = User::factory()->create(['role' => 'client']);

        RequestStatus::factory()->create(['name' => 'En attente']);
        RequestStatus::factory()->create(['name' => 'Terminé']);
        RequestStatus::factory()->create(['name' => 'En cours']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/request-statuses?search=En');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(2, count($data)); // "En attente" and "En cours"
    }

    /** @test */
    public function request_status_has_correct_attributes()
    {
        $status = RequestStatus::factory()->create([
            'name' => 'Test Status',
            'hex_color' => '#FF0000',
            'is_final' => true,
        ]);

        $this->assertEquals('Test Status', $status->display_name);
        $this->assertTrue($status->isFinal());
        $this->assertEquals('#FF0000', $status->valid_hex_color);
        $this->assertEquals([
            'hex' => '#FF0000',
            'name' => 'Test Status',
        ], $status->color_info);
    }

    /** @test */
    public function request_status_scopes_work_correctly()
    {
        RequestStatus::factory()->create([
            'is_final' => true,
            'name' => 'Status Final Scope Test'
        ]);

        RequestStatus::factory()->create([
            'is_final' => false,
            'name' => 'Status Non Final Scope Test'
        ]);

        RequestStatus::factory()->create([
            'hex_color' => '#FF0000',
            'is_final' => false,
            'name' => 'Status Rouge Scope Test'
        ]);

        $this->assertEquals(1, RequestStatus::final()->count());
        $this->assertEquals(2, RequestStatus::notFinal()->count()); // Non Final Status + Red Status (is_final = false)
        $this->assertEquals(1, RequestStatus::withColor('#FF0000')->count());
    }

    /** @test */
    public function invalid_hex_color_returns_default()
    {
        $status = new RequestStatus([
            'name' => 'Test',
            'hex_color' => 'invalid',
            'is_final' => false,
        ]);

        $this->assertEquals('#6c757d', $status->valid_hex_color);
    }
}
