<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // S'assurer que les migrations sont exécutées
        $this->artisan('migrate');

        // Ne pas exécuter les seeders automatiquement pour éviter les conflits
        // Chaque test peut les appeler manuellement si nécessaire
    }
}
