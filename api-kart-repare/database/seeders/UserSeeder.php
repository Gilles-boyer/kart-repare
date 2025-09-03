<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'System',
            'email' => 'admin@kartrepair.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+33123456789',
            'is_active' => true,
        ]);

        // Create Bureau Staff User
        User::factory()->create([
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'marie.dupont@kartrepair.com',
            'password' => Hash::make('password'),
            'role' => 'bureau_staff',
            'phone' => '+33123456790',
            'is_active' => true,
        ]);

        // Create Mechanic Users
        User::factory()->create([
            'first_name' => 'Jean',
            'last_name' => 'Martin',
            'email' => 'jean.martin@kartrepair.com',
            'password' => Hash::make('password'),
            'role' => 'mechanic',
            'phone' => '+33123456791',
            'is_active' => true,
        ]);

        User::factory()->create([
            'first_name' => 'Pierre',
            'last_name' => 'Durand',
            'email' => 'pierre.durand@kartrepair.com',
            'password' => Hash::make('password'),
            'role' => 'mechanic',
            'phone' => '+33123456792',
            'is_active' => true,
        ]);

        // Create Client Users
        User::factory()->create([
            'first_name' => 'Paul',
            'last_name' => 'Client',
            'email' => 'paul.client@example.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'phone' => '+33123456793',
            'company' => 'Karting Club Racing',
            'address' => '123 Rue du Circuit, 75000 Paris',
            'is_active' => true,
        ]);

        User::factory()->create([
            'first_name' => 'Sophie',
            'last_name' => 'Proprietaire',
            'email' => 'sophie.proprietaire@example.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'phone' => '+33123456794',
            'company' => 'Speed Karting Pro',
            'address' => '456 Avenue des Champions, 69000 Lyon',
            'is_active' => true,
        ]);

        // Create additional random users for testing
        User::factory(5)->client()->create();
        User::factory(3)->mechanic()->create();
        User::factory(2)->bureauStaff()->create();
    }
}
