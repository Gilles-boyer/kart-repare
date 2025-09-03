<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserTest extends TestCase
{
    use WithFaker; // Supprimé RefreshDatabase car c'est dans TestCase.php

    /**
     * Test user can login successfully.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'role'
                ],
                'token',
                'token_type'
            ]);
    }

    /**
     * Test inactive user cannot login.
     */
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_active' => false
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Votre compte est désactivé. Contactez l\'administrateur.'
            ]);
    }

    /**
     * Test user can register successfully.
     */
    public function test_user_can_register(): void
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+33123456789',
            'company' => 'Test Company'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'role'
                ],
                'token',
                'token_type'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'role' => 'client'
        ]);
    }

    /**
     * Test admin can view all users.
     */
    public function test_admin_can_view_all_users(): void
    {
        $admin = User::factory()->admin()->create();
        $users = User::factory()->count(3)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                        'is_active'
                    ]
                ]
            ]);
    }

    /**
     * Test client cannot view all users.
     */
    public function test_client_cannot_view_all_users(): void
    {
        $client = User::factory()->client()->create();

        Sanctum::actingAs($client);

        $response = $this->getJson('/api/users');

        $response->assertStatus(403);
    }

    /**
     * Test admin can create user.
     */
    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $userData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'mechanic',
            'phone' => '+33987654321'
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'role'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane.smith@example.com',
            'role' => 'mechanic'
        ]);
    }

    /**
     * Test client cannot create user.
     */
    public function test_client_cannot_create_user(): void
    {
        $client = User::factory()->client()->create();

        Sanctum::actingAs($client);

        $userData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'mechanic'
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(403);
    }

    /**
     * Test user can view own profile.
     */
    public function test_user_can_view_own_profile(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/users/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'role'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email
                ]
            ]);
    }

    /**
     * Test user can update own profile.
     */
    public function test_user_can_update_own_profile(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Old Name'
        ]);

        Sanctum::actingAs($user);

        $updateData = [
            'first_name' => 'New Name',
            'phone' => '+33123456789'
        ];

        $response = $this->putJson('/api/users/profile', $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'New Name',
            'phone' => '+33123456789'
        ]);
    }

    /**
     * Test admin can toggle user status.
     */
    public function test_admin_can_toggle_user_status(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['is_active' => true]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/users/{$user->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'is_active'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false
        ]);
    }

    /**
     * Test client cannot toggle user status.
     */
    public function test_client_cannot_toggle_user_status(): void
    {
        $client = User::factory()->client()->create();
        $user = User::factory()->create(['is_active' => true]);

        Sanctum::actingAs($client);

        $response = $this->patchJson("/api/users/{$user->id}/toggle-status");

        $response->assertStatus(403);
    }

    /**
     * Test admin can view users statistics.
     */
    public function test_admin_can_view_users_statistics(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->client()->count(3)->create();
        User::factory()->mechanic()->count(2)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'total_users',
                    'active_users',
                    'inactive_users',
                    'users_by_role' => [
                        'clients',
                        'bureau_staff',
                        'mechanics',
                        'admins'
                    ],
                    'recent_registrations',
                    'recent_logins'
                ]
            ]);
    }

    /**
     * Test validation errors on user creation.
     */
    public function test_user_creation_validation_errors(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [
            'email' => 'invalid-email',
            'password' => 'short',
            'role' => 'invalid-role'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password', 'role']);
    }

    /**
     * Test email uniqueness validation.
     */
    public function test_email_uniqueness_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com'
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'client'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
