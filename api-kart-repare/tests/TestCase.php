<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTruncation;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTruncation; // Meilleure solution pour Laravel 11+

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations for the test database (sans seeder pour éviter les conflits)
        $this->artisan('migrate:fresh');
    }
}
