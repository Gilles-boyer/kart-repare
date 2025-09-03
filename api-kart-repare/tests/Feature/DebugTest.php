<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DebugTest extends TestCase
{
    /**
     * Test debug pour voir l'erreur exacte.
     */
    public function test_debug_client_access(): void
    {
        // Créer un client
        $client = User::factory()->client()->create();

        // Activer Sanctum
        Sanctum::actingAs($client);

        try {
            $response = $this->getJson('/api/users');
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
