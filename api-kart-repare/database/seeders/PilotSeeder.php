<?php

namespace Database\Seeders;

use App\Models\Pilot;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PilotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer tous les clients existants
        $clients = User::where('role', 'client')->get();

        if ($clients->isEmpty()) {
            $this->command->info('Aucun client trouvé. Création de clients d\'abord...');
            // Créer quelques clients si aucun n'existe
            $clients = User::factory()->count(5)->create(['role' => 'client']);
        }

        // Créer des pilotes pour chaque client
        foreach ($clients as $client) {
            // Chaque client peut avoir entre 1 et 3 pilotes
            $pilotCount = rand(1, 3);

            Pilot::factory()
                ->count($pilotCount)
                ->forClient($client)
                ->create();

            $this->command->info("Créé {$pilotCount} pilote(s) pour le client {$client->full_name}");
        }

        // Créer quelques pilotes mineurs
        Pilot::factory()
            ->count(3)
            ->minor()
            ->forClient($clients->random())
            ->create();

        $this->command->info('Pilotes créés avec succès !');
    }
}
