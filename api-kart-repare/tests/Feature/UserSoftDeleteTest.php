<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserSoftDeleteTest extends TestCase
{
    /**
     * Test admin can view trashed users.
     */
    public function test_admin_can_view_trashed_users(): void
    {
        // Créer un admin
        $admin = User::factory()->admin()->create();

        // Créer et supprimer un utilisateur
        $deletedUser = User::factory()->client()->create();
        $deletedUser->delete();

        // Activer Sanctum pour l'admin
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users/trashed');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                        'deleted_at',
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    /**
     * Test client cannot view trashed users.
     */
    public function test_client_cannot_view_trashed_users(): void
    {
        $client = User::factory()->client()->create();

        Sanctum::actingAs($client);

        $response = $this->getJson('/api/users/trashed');

        $response->assertStatus(403);
    }

    /**
     * Test admin can restore soft deleted user.
     */
    public function test_admin_can_restore_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();

        // Supprimer l'utilisateur (soft delete)
        $user->delete();

        $this->assertSoftDeleted($user);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/users/{$user->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Utilisateur restauré avec succès'
            ]);

        // Vérifier que l'utilisateur est restauré
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null
        ]);
    }

    /**
     * Test client cannot restore user.
     */
    public function test_client_cannot_restore_user(): void
    {
        $client = User::factory()->client()->create();
        $user = User::factory()->client()->create();

        $user->delete();

        Sanctum::actingAs($client);

        $response = $this->patchJson("/api/users/{$user->id}/restore");

        $response->assertStatus(403);
    }

    /**
     * Test admin can force delete user.
     */
    public function test_admin_can_force_delete_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();

        // Supprimer l'utilisateur (soft delete)
        $user->delete();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/users/{$user->id}/force-delete");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Utilisateur supprimé définitivement avec succès'
            ]);

        // Vérifier que l'utilisateur n'existe plus du tout
        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);
    }

    /**
     * Test admin cannot force delete themselves.
     */
    public function test_admin_cannot_force_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        // Supprimer l'admin (soft delete)
        $admin->delete();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/users/{$admin->id}/force-delete");

        $response->assertStatus(403);
    }

    /**
     * Test soft delete functionality in user deletion.
     */
    public function test_user_deletion_is_soft_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->client()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200);

        // Vérifier que l'utilisateur est soft deleted
        $this->assertSoftDeleted($user);

        // Vérifier qu'il n'apparaît pas dans les requêtes normales
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'deleted_at' => null
        ]);

        // Mais qu'il existe toujours avec deleted_at
        $this->assertDatabaseHas('users', [
            'id' => $user->id
        ]);
    }

    /**
     * Test statistics include deleted users.
     */
    public function test_statistics_include_deleted_users(): void
    {
        $admin = User::factory()->admin()->create();

        // Créer et supprimer des utilisateurs
        $client = User::factory()->client()->create();
        $mechanic = User::factory()->mechanic()->create();

        $client->delete();
        $mechanic->delete();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_users',
                    'deleted_users',
                    'deleted_users_by_role' => [
                        'clients',
                        'mechanics',
                        'bureau_staff',
                        'admins'
                    ],
                    'recent_deletions'
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(2, $data['deleted_users']);
        $this->assertEquals(1, $data['deleted_users_by_role']['clients']);
        $this->assertEquals(1, $data['deleted_users_by_role']['mechanics']);
    }

    /**
     * Test search functionality in trashed users.
     */
    public function test_can_search_trashed_users(): void
    {
        $admin = User::factory()->admin()->create();

        // Créer des utilisateurs avec des noms très spécifiques
        $user1 = User::factory()->client()->create(['first_name' => 'JohnUnique', 'last_name' => 'DoeSpecial']);
        $user2 = User::factory()->client()->create(['first_name' => 'JaneUnique', 'last_name' => 'SmithSpecial']);

        $user1->delete();
        $user2->delete();

        Sanctum::actingAs($admin);

        // Chercher "JohnUnique" dans les utilisateurs supprimés
        $response = $this->getJson('/api/users/trashed?search=JohnUnique');

        $response->assertStatus(200);

        $users = $response->json('data');
        $this->assertCount(1, $users);
        $this->assertEquals('JohnUnique', $users[0]['first_name']);
    }
}
