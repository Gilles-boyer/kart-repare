<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RepairRequest;
use App\Models\RepairRequestProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RepairRequestProductControllerTest extends TestCase
{
    use WithFaker; // Supprimé RefreshDatabase car c'est dans TestCase.php

    private User $admin;
    private User $bureauStaff;
    private User $mechanic;
    private User $client;
    private RepairRequest $repairRequest;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with correct roles
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->bureauStaff = User::factory()->create(['role' => 'bureau_staff']);
        $this->mechanic = User::factory()->create(['role' => 'mechanic']);
        $this->client = User::factory()->create(['role' => 'client']);

        // Create test data
        $this->repairRequest = RepairRequest::factory()->create();
        $this->product = Product::factory()->create(['price' => 25.50]);
    }

    #[Test]
    public function admin_can_view_all_repair_request_products()
    {
        RepairRequestProduct::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/repair-request-products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'repair_request_id',
                        'product_id',
                        'quantity',
                        'unit_price',
                        'total_price',
                        'priority',
                        'status',
                        'is_invoiced',
                        'is_completed',
                        'is_approved',
                    ]
                ],
                'meta',
                'links'
            ]);
    }

    /** @test */
    public function admin_can_create_repair_request_product()
    {
        $data = [
            'repair_request_id' => $this->repairRequest->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'priority' => 'high',
            'note' => 'Urgent replacement needed',
            'unit_price' => 25.50,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/repair-request-products', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'repair_request_id',
                    'product_id',
                    'quantity',
                    'unit_price',
                    'total_price',
                    'priority',
                    'note',
                ]
            ]);

        $this->assertDatabaseHas('repair_request_products', [
            'repair_request_id' => $this->repairRequest->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'total_price' => 51.00, // 2 * 25.50
            'priority' => 'high',
        ]);
    }

    /** @test */
    public function cannot_create_duplicate_repair_request_product()
    {
        RepairRequestProduct::factory()->create([
            'repair_request_id' => $this->repairRequest->id,
            'product_id' => $this->product->id,
        ]);

        $data = [
            'repair_request_id' => $this->repairRequest->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 25.50,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/repair-request-products', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    /** @test */
    public function admin_can_update_repair_request_product()
    {
        $repairRequestProduct = RepairRequestProduct::factory()->create([
            'quantity' => 1,
            'unit_price' => 25.50,
            'priority' => 'medium',
        ]);

        $data = [
            'quantity' => 3,
            'priority' => 'high',
            'note' => 'Updated note',
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/repair-request-products/{$repairRequestProduct->id}", $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        $repairRequestProduct->refresh();
        $this->assertEquals(3, $repairRequestProduct->quantity);
        $this->assertEquals('high', $repairRequestProduct->priority);
        $this->assertEquals(76.50, $repairRequestProduct->total_price); // 3 * 25.50
    }

    /** @test */
    public function admin_can_delete_repair_request_product()
    {
        $repairRequestProduct = RepairRequestProduct::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/repair-request-products/{$repairRequestProduct->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Produit de demande de réparation supprimé avec succès.']);

        $this->assertDatabaseMissing('repair_request_products', [
            'id' => $repairRequestProduct->id,
        ]);
    }

    /** @test */
    public function cannot_delete_invoiced_repair_request_product()
    {
        $repairRequestProduct = RepairRequestProduct::factory()->invoiced()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/repair-request-products/{$repairRequestProduct->id}");

        $response->assertStatus(403); // Forbidden by policy
    }

    /** @test */
    public function admin_can_invoice_repair_request_product()
    {
        $repairRequestProduct = RepairRequestProduct::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/repair-request-products/{$repairRequestProduct->id}/invoice");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Produit de demande de réparation facturé avec succès.']);

        $repairRequestProduct->refresh();
        $this->assertTrue($repairRequestProduct->is_invoiced);
        $this->assertNotNull($repairRequestProduct->invoiced_at);
        $this->assertEquals($this->admin->id, $repairRequestProduct->invoiced_by);
    }

    /** @test */
    public function admin_can_complete_invoiced_repair_request_product()
    {
        $repairRequestProduct = RepairRequestProduct::factory()->invoiced()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/repair-request-products/{$repairRequestProduct->id}/complete");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Produit de demande de réparation terminé avec succès.']);

        $repairRequestProduct->refresh();
        $this->assertTrue($repairRequestProduct->is_completed);
        $this->assertNotNull($repairRequestProduct->completed_at);
        $this->assertEquals($this->admin->id, $repairRequestProduct->completed_by);
    }

    /** @test */
    public function admin_can_approve_completed_repair_request_product()
    {
        $repairRequestProduct = RepairRequestProduct::factory()->completed()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/repair-request-products/{$repairRequestProduct->id}/approve");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Produit de demande de réparation approuvé avec succès.']);

        $repairRequestProduct->refresh();
        $this->assertTrue($repairRequestProduct->is_approved);
        $this->assertNotNull($repairRequestProduct->approved_at);
    }

    /** @test */
    public function cannot_complete_non_invoiced_repair_request_product()
    {
        $repairRequestProduct = RepairRequestProduct::factory()->create(); // Not invoiced

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/repair-request-products/{$repairRequestProduct->id}/complete");

        $response->assertStatus(403); // Forbidden by policy
    }

    /** @test */
    public function cannot_approve_non_completed_repair_request_product()
    {
        $repairRequestProduct = RepairRequestProduct::factory()->invoiced()->create(); // Not completed

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/repair-request-products/{$repairRequestProduct->id}/approve");

        $response->assertStatus(403); // Forbidden by policy
    }

    /** @test */
    public function admin_can_revert_invoice_status()
    {
        $repairRequestProduct = RepairRequestProduct::factory()->invoiced()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/repair-request-products/{$repairRequestProduct->id}/revert-invoice");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Statut de facturation du produit annulé avec succès.']);

        $repairRequestProduct->refresh();
        $this->assertFalse($repairRequestProduct->is_invoiced);
        $this->assertNull($repairRequestProduct->invoiced_at);
        $this->assertNull($repairRequestProduct->invoiced_by);
    }

    /** @test */
    public function admin_can_revert_completion_status()
    {
        $repairRequestProduct = RepairRequestProduct::factory()->completed()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/repair-request-products/{$repairRequestProduct->id}/revert-completion");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Statut de completion du produit annulé avec succès.']);

        $repairRequestProduct->refresh();
        $this->assertFalse($repairRequestProduct->is_completed);
        $this->assertNull($repairRequestProduct->completed_at);
        $this->assertNull($repairRequestProduct->completed_by);
    }

    /** @test */
    public function admin_can_get_statistics()
    {
        RepairRequestProduct::factory()->count(5)->create();
        RepairRequestProduct::factory()->invoiced()->count(3)->create();
        RepairRequestProduct::factory()->completed()->count(2)->create();
        RepairRequestProduct::factory()->approved()->count(1)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/repair-request-products/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_products',
                    'total_value',
                    'average_value',
                    'invoiced_count',
                    'completed_count',
                    'approved_count',
                    'pending_count',
                    'by_priority' => [
                        'high',
                        'medium',
                        'low'
                    ],
                    'workflow_distribution' => [
                        'created',
                        'invoiced',
                        'completed',
                        'approved'
                    ]
                ]
            ]);
    }

    /** @test */
    public function mechanic_can_only_access_assigned_repair_request_products()
    {
        // Create a repair request assigned to mechanic
        $assignedRepairRequest = RepairRequest::factory()->create([
            'assigned_to' => $this->mechanic->id
        ]);
        $assignedProduct = RepairRequestProduct::factory()->create([
            'repair_request_id' => $assignedRepairRequest->id
        ]);

        // Create a repair request NOT assigned to mechanic
        $unassignedRepairRequest = RepairRequest::factory()->create();
        $unassignedProduct = RepairRequestProduct::factory()->create([
            'repair_request_id' => $unassignedRepairRequest->id
        ]);

        // Mechanic can view assigned product
        $response = $this->actingAs($this->mechanic, 'sanctum')
            ->getJson("/api/repair-request-products/{$assignedProduct->id}");
        $response->assertStatus(200);

        // Mechanic cannot view unassigned product
        $response = $this->actingAs($this->mechanic, 'sanctum')
            ->getJson("/api/repair-request-products/{$unassignedProduct->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_create_repair_request_products()
    {
        $data = [
            'repair_request_id' => $this->repairRequest->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 25.50,
            'priority' => 'medium',
        ];

        $response = $this->actingAs($this->client, 'sanctum')
            ->postJson('/api/repair-request-products', $data);

        $response->assertStatus(403); // Forbidden by policy
    }

    /** @test */
    public function unauthenticated_user_cannot_access_repair_request_products()
    {
        $response = $this->getJson('/api/repair-request-products');
        $response->assertStatus(401); // Unauthenticated
    }

    /** @test */
    public function can_filter_repair_request_products_by_status()
    {
        RepairRequestProduct::factory()->count(2)->create();
        RepairRequestProduct::factory()->invoiced()->count(3)->create();
        RepairRequestProduct::factory()->completed()->count(1)->create();

        // Filter by invoiced
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/repair-request-products?invoiced=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(4, $data); // 3 invoiced + 1 completed (also invoiced)
    }

    /** @test */
    public function can_search_repair_request_products()
    {
        $product = Product::factory()->create(['name' => 'Special Engine Part']);
        RepairRequestProduct::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/repair-request-products?search=Special');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($product->id, $data[0]['product_id']);
    }
}
