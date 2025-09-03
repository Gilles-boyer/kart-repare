<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Utiliser seulement RefreshDatabase sans seeder automatique
    }

    /** @test */
    public function authenticated_user_can_view_products()
    {
        $user = User::factory()->create(['role' => 'client']);
        Product::factory()->count(3)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
    }

    /** @test */
    public function unauthenticated_user_cannot_view_products()
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.'
                 ]);
    }

    /** @test */
    public function admin_can_create_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $productData = [
            'name' => 'Bougie d\'allumage NGK',
            'description' => 'Bougie haute performance pour kart',
            'ref' => 'NGK-B9HS',
            'price' => 15.99,
            'image' => 'products/bougie-ngk.jpg',
            'in_stock' => 25,
            'unity' => 'piece',
            'min_stock' => 5,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'name' => 'Bougie d\'allumage NGK',
                     'ref' => 'NGK-B9HS',
                     'price' => 15.99,
                 ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Bougie d\'allumage NGK',
            'ref' => 'NGK-B9HS',
        ]);
    }

    /** @test */
    public function client_cannot_create_product()
    {
        $client = User::factory()->create(['role' => 'client']);

        Sanctum::actingAs($client);

        $productData = [
            'name' => 'Test Product',
            'ref' => 'TEST-001',
            'price' => 10.00,
            'in_stock' => 5,
            'unity' => 'piece',
            'min_stock' => 2,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(403);
    }

    /** @test */
    public function bureau_staff_can_create_product()
    {
        $staff = User::factory()->create(['role' => 'bureau_staff']);

        Sanctum::actingAs($staff);

        $productData = [
            'name' => 'Filtre à air K&N',
            'description' => 'Filtre haute performance',
            'ref' => 'KN-HA6098',
            'price' => 85.50,
            'in_stock' => 10,
            'unity' => 'piece',
            'min_stock' => 3,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201);
    }

    /** @test */
    public function user_can_view_single_product()
    {
        $user = User::factory()->create(['role' => 'client']);
        $product = Product::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $product->id,
                     'name' => $product->name,
                 ]);
    }

    /** @test */
    public function admin_can_update_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create([
            'name' => 'Ancien nom',
            'price' => 10.00,
        ]);

        Sanctum::actingAs($admin);

        $updateData = [
            'name' => 'Nouveau nom',
            'price' => 15.00,
        ];

        $response = $this->putJson("/api/products/{$product->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'name' => 'Nouveau nom',
                     'price' => 15.00,
                 ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Nouveau nom',
        ]);
    }

    /** @test */
    public function client_cannot_update_product()
    {
        $client = User::factory()->create(['role' => 'client']);
        $product = Product::factory()->create();

        Sanctum::actingAs($client);

        $updateData = ['name' => 'Updated name'];

        $response = $this->putJson("/api/products/{$product->id}", $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    /** @test */
    public function bureau_staff_cannot_delete_product()
    {
        $staff = User::factory()->create(['role' => 'bureau_staff']);
        $product = Product::factory()->create();

        Sanctum::actingAs($staff);

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function product_creation_validation_errors()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $invalidData = [
            'name' => '', // Required
            'ref' => '', // Required
            'price' => -10, // Must be positive
            'in_stock' => -5, // Must be positive
            'unity' => 'invalid', // Must be valid enum
            'min_stock' => -2, // Must be positive
        ];

        $response = $this->postJson('/api/products', $invalidData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'name',
                     'ref',
                     'price',
                     'in_stock',
                     'unity',
                     'min_stock',
                 ]);
    }

    /** @test */
    public function unique_ref_validation_on_creation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Product::factory()->create(['ref' => 'EXISTING-REF']);

        Sanctum::actingAs($admin);

        $productData = [
            'name' => 'New Product',
            'ref' => 'EXISTING-REF', // Already exists
            'price' => 10.00,
            'in_stock' => 5,
            'unity' => 'piece',
            'min_stock' => 2,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['ref']);
    }

    /** @test */
    public function admin_can_update_stock()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['in_stock' => 10]);

        Sanctum::actingAs($admin);

        // Add stock
        $response = $this->postJson("/api/products/{$product->id}/stock/add", [
            'quantity' => 5
        ]);

        $response->assertStatus(200);
        $product->refresh();
        $this->assertEquals(15, $product->in_stock);

        // Remove stock
        $response = $this->postJson("/api/products/{$product->id}/stock/reduce", [
            'quantity' => 3
        ]);

        $response->assertStatus(200);
        $product->refresh();
        $this->assertEquals(12, $product->in_stock);
    }

    /** @test */
    public function client_cannot_update_stock()
    {
        $client = User::factory()->create(['role' => 'client']);
        $product = Product::factory()->create();

        Sanctum::actingAs($client);

        $response = $this->postJson("/api/products/{$product->id}/stock/add", [
            'quantity' => 5
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_statistics()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Product::factory()->count(5)->create(['unity' => 'piece']);
        Product::factory()->count(3)->create(['unity' => 'liters']);
        Product::factory()->count(2)->outOfStock()->create();
        Product::factory()->count(4)->lowStock()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/products/statistics');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total',
                     'in_stock',
                     'out_of_stock',
                     'needs_restock',
                     'by_unity',
                     'prices',
                     'stock_value',
                     'low_stock_products',
                     'deleted',
                 ]);
    }

    /** @test */
    public function client_cannot_view_statistics()
    {
        $client = User::factory()->create(['role' => 'client']);

        Sanctum::actingAs($client);

        $response = $this->getJson('/api/products/statistics');

        $response->assertStatus(403);
    }

    /** @test */
    public function can_filter_products_by_unity()
    {
        $user = User::factory()->create(['role' => 'admin']);

        Product::factory()->count(3)->create(['unity' => 'piece']);
        Product::factory()->count(2)->create(['unity' => 'liters']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/products?unity=piece');

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
    }

    /** @test */
    public function can_filter_products_by_stock_status()
    {
        $user = User::factory()->create(['role' => 'admin']);

        Product::factory()->count(3)->create(['in_stock' => 10, 'min_stock' => 5]);
        Product::factory()->count(2)->outOfStock()->create();

        Sanctum::actingAs($user);

        // Test out of stock filter
        $response = $this->getJson('/api/products?stock_status=out_of_stock');
        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));

        // Test in stock filter
        $response = $this->getJson('/api/products?stock_status=in_stock');
        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
    }

    /** @test */
    public function can_search_products_by_name_and_ref()
    {
        $user = User::factory()->create(['role' => 'admin']);

        Product::factory()->create(['name' => 'Bougie NGK', 'ref' => 'NGK-001']);
        Product::factory()->create(['name' => 'Filtre K&N', 'ref' => 'KN-002']);
        Product::factory()->create(['name' => 'Huile Motul', 'ref' => 'MTL-003']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/products?search=NGK');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, count($data));
        $this->assertStringContainsString('NGK', $data[0]['name']);
    }

    /** @test */
    public function can_filter_products_by_price_range()
    {
        $user = User::factory()->create(['role' => 'admin']);

        Product::factory()->create(['price' => 10.00]);
        Product::factory()->create(['price' => 25.50]);
        Product::factory()->create(['price' => 50.00]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/products?min_price=20&max_price=40');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, count($data));
        $this->assertEquals(25.50, $data[0]['price']);
    }

    /** @test */
    public function can_get_low_stock_products()
    {
        $user = User::factory()->create(['role' => 'admin']);

        Product::factory()->count(3)->create(['in_stock' => 10, 'min_stock' => 5]);
        Product::factory()->count(2)->lowStock()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/products/low-stock');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
    }

    /** @test */
    public function product_has_correct_computed_attributes()
    {
        $product = Product::factory()->create([
            'unity' => 'piece',
            'in_stock' => 8,
            'min_stock' => 10,
            'price' => 25.50,
        ]);

        $this->assertEquals('Pièce(s)', $product->unity_display);
        $this->assertEquals('low_stock', $product->stock_status);
        $this->assertTrue($product->needs_restock);
        $this->assertTrue($product->isAvailable());
        $this->assertFalse($product->isOutOfStock());
        $this->assertEquals(204.00, $product->in_stock * $product->price); // Stock value
    }

    /** @test */
    public function product_scopes_work_correctly()
    {
        // Nettoyer toutes les données existantes pour ce test
        Product::withTrashed()->forceDelete();

        // Créer les produits avec des valeurs spécifiques
        Product::factory()->create([
            'name' => 'Product In Stock',
            'in_stock' => 10,
            'min_stock' => 5,
            'unity' => 'piece',
            'price' => 25.00
        ]);
        Product::factory()->create([
            'name' => 'Product Out Of Stock',
            'in_stock' => 0,
            'min_stock' => 5,
            'unity' => 'piece',
            'price' => 25.00
        ]);
        Product::factory()->create([
            'name' => 'Product Low Stock',
            'in_stock' => 2,
            'min_stock' => 5,
            'unity' => 'piece',
            'price' => 25.00
        ]);
        Product::factory()->create([
            'name' => 'Product Liters',
            'in_stock' => 10,
            'min_stock' => 5,
            'unity' => 'liters',
            'price' => 25.00
        ]);
        Product::factory()->create([
            'name' => 'Product Expensive',
            'in_stock' => 10,
            'min_stock' => 5,
            'unity' => 'piece',
            'price' => 100.00
        ]);

        $this->assertEquals(4, Product::inStock()->count()); // 4 produits ont in_stock > 0 (tous sauf out of stock)
        $this->assertEquals(1, Product::outOfStock()->count()); // seul "Product Out Of Stock" a in_stock <= 0
        $this->assertEquals(2, Product::whereRaw('in_stock <= min_stock')->count()); // "Product Low Stock" (2 <= 5) et "Product Out Of Stock" (0 <= 5)
        $this->assertEquals(1, Product::byUnity('liters')->count()); // seul "Product Liters"
        $this->assertEquals(1, Product::withMinPrice(50)->count()); // seul "Product Expensive" a 100 >= 50
    }

    /** @test */
    public function product_stock_methods_work_correctly()
    {
        $product = Product::factory()->create(['in_stock' => 10]);

        // Test add stock
        $product->addStock(5);
        $this->assertEquals(15, $product->fresh()->in_stock);

        // Test reduce stock - success
        $result = $product->fresh()->reduceStock(3);
        $this->assertTrue($result);
        $this->assertEquals(12, $product->fresh()->in_stock);

        // Test reduce stock - insufficient stock
        $result = $product->fresh()->reduceStock(20);
        $this->assertFalse($result);
        $this->assertEquals(12, $product->fresh()->in_stock); // Should remain unchanged
    }
}
