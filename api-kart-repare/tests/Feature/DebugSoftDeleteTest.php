<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DebugSoftDeleteTest extends TestCase
{
    /**
     * Test debug pour voir l'erreur exacte.
     */
    public function test_debug_admin_force_delete(): void
    {
        // Créer un admin
        $admin = User::factory()->admin()->create();

        // Le supprimer (soft delete)
        $admin->delete();

        // Activer Sanctum
        Sanctum::actingAs($admin);

        try {
            $response = $this->deleteJson("/api/users/{$admin->id}/force-delete");
            dump('Response status:', $response->status());
            dump('Response content:', $response->getContent());
        } catch (\Exception $e) {
            dump('Exception:', $e->getMessage());
            dump('Trace:', $e->getTraceAsString());
        }

        // Juste pour que le test soit valide
        $this->assertTrue(true);
    }
}
