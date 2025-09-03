<?php

namespace Database\Seeders;

use App\Models\Kart;
use App\Models\Pilot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer tous les pilotes existants
        $pilots = Pilot::all();

        if ($pilots->isEmpty()) {
            $this->command->info('Aucun pilote trouvé. Création de pilotes d\'abord...');
            // Créer quelques pilotes si aucun n'existe
            $pilots = Pilot::factory()->count(10)->create();
        }

        $brands = ['Tony Kart', 'CRG', 'Birel ART', 'OTK', 'Kosmic'];

        // Créer des karts pour chaque pilote
        foreach ($pilots as $pilot) {
            // Chaque pilote peut avoir entre 0 et 2 karts
            $kartCount = rand(0, 2);

            if ($kartCount > 0) {
                for ($i = 0; $i < $kartCount; $i++) {
                    $brand = fake()->randomElement($brands);

                    Kart::factory()
                        ->forPilot($pilot)
                        ->ofBrand($brand)
                        ->create();
                }

                $this->command->info("Créé {$kartCount} kart(s) pour le pilote {$pilot->full_name}");
            }
        }

        // Créer quelques karts vintage pour la diversité
        Kart::factory()
            ->count(5)
            ->vintage()
            ->create();

        // Créer quelques karts modernes
        Kart::factory()
            ->count(8)
            ->modern()
            ->create();

        // Créer quelques karts inactifs
        Kart::factory()
            ->count(3)
            ->inactive()
            ->create();

        $totalKarts = Kart::count();
        $this->command->info("Total de {$totalKarts} karts créés avec succès !");

        // Afficher quelques statistiques
        $activeKarts = Kart::active()->count();
        $inactiveKarts = Kart::inactive()->count();
        $brands = Kart::select('brand')->distinct()->pluck('brand');

        $this->command->info("Statistiques:");
        $this->command->info("- Karts actifs: {$activeKarts}");
        $this->command->info("- Karts inactifs: {$inactiveKarts}");
        $this->command->info("- Marques représentées: " . $brands->join(', '));
    }
}
