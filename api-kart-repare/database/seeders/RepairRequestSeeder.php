<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RepairRequest;
use App\Models\Kart;
use App\Models\RequestStatus;
use App\Models\User;

class RepairRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // S'assurer qu'il y a des données dans les tables liées
        if (Kart::count() === 0) {
            $this->command->info('Pas de karts trouvés. Veuillez d\'abord exécuter le KartSeeder.');
            return;
        }

        if (RequestStatus::count() === 0) {
            $this->command->info('Pas de statuts trouvés. Veuillez d\'abord exécuter le RequestStatusSeeder.');
            return;
        }

        if (User::count() === 0) {
            $this->command->info('Pas d\'utilisateurs trouvés. Veuillez d\'abord exécuter le UserSeeder.');
            return;
        }

        $karts = Kart::all();
        $statuses = RequestStatus::all();
        $users = User::all();
        $mechanics = User::whereIn('role', ['admin', 'bureau_staff'])->get();

        // Statuts standards pour les différents états
        $pendingStatus = $statuses->firstWhere('name', 'En attente') ?? $statuses->first();
        $inProgressStatus = $statuses->firstWhere('name', 'En cours') ?? $statuses->skip(1)->first();
        $completedStatus = $statuses->firstWhere('name', 'Terminé') ?? $statuses->where('is_final', true)->first();

        // Créer des demandes de réparation variées
        $repairs = [
            // Demandes à haute priorité (urgentes)
            [
                'title' => 'Réparation moteur cassé - Kart ' . $karts->random()->number,
                'description' => 'Le moteur ne démarre plus après la course de samedi. Vérification nécessaire du système d\'allumage et de la compression.',
                'priority' => 'high',
                'estimated_cost' => 450.00,
                'actual_cost' => 0,
                'status_id' => $pendingStatus->id,
                'estimated_completion' => now()->addDays(3),
            ],
            [
                'title' => 'Système de freinage défaillant',
                'description' => 'Les freins ne répondent plus correctement. Problème de sécurité critique à résoudre immédiatement.',
                'priority' => 'high',
                'estimated_cost' => 380.00,
                'actual_cost' => 0,
                'status_id' => $pendingStatus->id,
                'estimated_completion' => now()->addDays(2),
            ],

            // Demandes à priorité moyenne (maintenance régulière)
            [
                'title' => 'Changement des pneus avant',
                'description' => 'Usure importante des pneus avant après 50h d\'utilisation. Remplacement nécessaire.',
                'priority' => 'medium',
                'estimated_cost' => 180.00,
                'actual_cost' => 185.50,
                'status_id' => $completedStatus->id,
                'estimated_completion' => now()->subDays(5),
                'started_at' => now()->subDays(7),
                'completed_at' => now()->subDays(2),
            ],
            [
                'title' => 'Révision générale 100h',
                'description' => 'Révision complète du moteur, vérification des niveaux, changement des filtres et bougies.',
                'priority' => 'medium',
                'estimated_cost' => 320.00,
                'actual_cost' => 0,
                'status_id' => $inProgressStatus->id,
                'estimated_completion' => now()->addDays(5),
                'started_at' => now()->subDays(2),
            ],
            [
                'title' => 'Réglage de la direction',
                'description' => 'La direction tire légèrement à droite. Ajustement de la géométrie nécessaire.',
                'priority' => 'medium',
                'estimated_cost' => 120.00,
                'actual_cost' => 110.00,
                'status_id' => $completedStatus->id,
                'estimated_completion' => now()->subDays(3),
                'started_at' => now()->subDays(5),
                'completed_at' => now()->subDays(1),
            ],

            // Demandes à faible priorité (entretien préventif)
            [
                'title' => 'Nettoyage et lubrification chaîne',
                'description' => 'Entretien préventif de la chaîne de transmission.',
                'priority' => 'low',
                'estimated_cost' => 45.00,
                'actual_cost' => 40.00,
                'status_id' => $completedStatus->id,
                'estimated_completion' => now()->subDays(10),
                'started_at' => now()->subDays(12),
                'completed_at' => now()->subDays(8),
            ],
            [
                'title' => 'Vérification des niveaux',
                'description' => 'Contrôle hebdomadaire des niveaux d\'huile et de liquide de refroidissement.',
                'priority' => 'low',
                'estimated_cost' => 25.00,
                'actual_cost' => 0,
                'status_id' => $pendingStatus->id,
                'estimated_completion' => now()->addDays(7),
            ],
            [
                'title' => 'Remplacement autocollants numéro',
                'description' => 'Les autocollants de numérotation sont abîmés et doivent être remplacés.',
                'priority' => 'low',
                'estimated_cost' => 15.00,
                'actual_cost' => 18.50,
                'status_id' => $completedStatus->id,
                'estimated_completion' => now()->subDays(1),
                'started_at' => now()->subDays(2),
                'completed_at' => now()->subHours(6),
            ],

            // Demande en retard
            [
                'title' => 'Révision moteur en retard',
                'description' => 'Révision qui devait être terminée la semaine dernière mais qui a pris du retard.',
                'priority' => 'medium',
                'estimated_cost' => 280.00,
                'actual_cost' => 0,
                'status_id' => $inProgressStatus->id,
                'estimated_completion' => now()->subDays(3), // En retard
                'started_at' => now()->subDays(10),
            ],

            // Demande avec coût dépassé
            [
                'title' => 'Réparation carrosserie endommagée',
                'description' => 'Réparation suite à un accrochage. Les dégâts étaient plus importants que prévu.',
                'priority' => 'medium',
                'estimated_cost' => 200.00,
                'actual_cost' => 285.75, // Dépassement de budget
                'status_id' => $completedStatus->id,
                'estimated_completion' => now()->subDays(4),
                'started_at' => now()->subDays(8),
                'completed_at' => now()->subDays(1),
            ],
        ];

        foreach ($repairs as $repairData) {
            $repair = RepairRequest::create([
                'kart_id' => $karts->random()->id,
                'title' => $repairData['title'],
                'description' => $repairData['description'],
                'status_id' => $repairData['status_id'],
                'priority' => $repairData['priority'],
                'created_by' => $users->random()->id,
                'assigned_to' => $mechanics->isNotEmpty() ? $mechanics->random()->id : null,
                'estimated_cost' => $repairData['estimated_cost'],
                'actual_cost' => $repairData['actual_cost'],
                'estimated_completion' => $repairData['estimated_completion'],
                'started_at' => $repairData['started_at'] ?? null,
                'completed_at' => $repairData['completed_at'] ?? null,
            ]);

            $statusText = $repair->completed_at ? '[TERMINÉ]' : ($repair->started_at ? '[EN COURS]' : '[EN ATTENTE]');
            $priorityText = strtoupper($repair->priority);

            $this->command->info("Créé: {$repair->title} - Priorité: {$priorityText} {$statusText}");
        }

        // Statistiques finales
        $this->command->info("\n=== Statistiques des demandes de réparation créées ===");
        $this->command->info("Total: " . RepairRequest::count() . " demandes");

        $this->command->info("\nPar priorité:");
        foreach (['low', 'medium', 'high'] as $priority) {
            $count = RepairRequest::where('priority', $priority)->count();
            $priorityName = match($priority) {
                'low' => 'Faible',
                'medium' => 'Moyenne',
                'high' => 'Haute'
            };
            $this->command->info("- {$priorityName}: {$count}");
        }

        $this->command->info("\nPar statut:");
        $pending = RepairRequest::whereNull('started_at')->count();
        $active = RepairRequest::whereNotNull('started_at')->whereNull('completed_at')->count();
        $completed = RepairRequest::whereNotNull('completed_at')->count();
        $this->command->info("- En attente: {$pending}");
        $this->command->info("- En cours: {$active}");
        $this->command->info("- Terminées: {$completed}");

        $overdue = RepairRequest::where('estimated_completion', '<', now())
                                ->whereNull('completed_at')->count();
        $this->command->info("- En retard: {$overdue}");

        $totalEstimated = RepairRequest::sum('estimated_cost');
        $totalActual = RepairRequest::sum('actual_cost');
        $this->command->info("\nCoûts:");
        $this->command->info("- Estimé total: " . number_format($totalEstimated, 2) . " €");
        $this->command->info("- Réel total: " . number_format($totalActual, 2) . " €");

        $this->command->info("\nDemandes de réparation créées avec succès !");
    }
}
