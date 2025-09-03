<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,                  // D'abord les utilisateurs
            PilotSeeder::class,                 // Puis les pilotes (dépendent des users)
            KartSeeder::class,                  // Puis les karts (dépendent des pilotes)
            RequestStatusSeeder::class,         // Puis les statuts de demandes
            ProductSeeder::class,               // Puis les produits
            RepairRequestSeeder::class,         // Puis les demandes de réparation (dépendent de tout)
            RepairRequestProductSeeder::class,  // Enfin les produits de demandes de réparation
        ]);
    }
}
