<?php

namespace Database\Seeders;

use App\Models\RepairRequestProduct;
use App\Models\RepairRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RepairRequestProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vérifier que les dépendances existent
        if (RepairRequest::count() === 0) {
            $this->command->warn('Aucune demande de réparation trouvée. Exécutez d\'abord RepairRequestSeeder.');
            return;
        }

        if (Product::count() === 0) {
            $this->command->warn('Aucun produit trouvé. Exécutez d\'abord ProductSeeder.');
            return;
        }

        if (User::whereIn('role', ['bureau_staff', 'mechanic', 'admin'])->count() === 0) {
            $this->command->warn('Aucun utilisateur bureau/mécanicien trouvé. Exécutez d\'abord UserSeeder.');
            return;
        }

        $this->command->info('🔧 Création des produits pour les demandes de réparation...');

        // Récupérer les entités existantes
        $repairRequests = RepairRequest::all();
        $products = Product::all();
        $bureauStaff = User::whereIn('role', ['bureau_staff', 'admin'])->get();
        $mechanics = User::whereIn('role', ['mechanic', 'admin'])->get();

        // Statistiques pour l'affichage
        $totalCreated = 0;
        $statusStats = [
            'pending' => 0,
            'invoiced' => 0,
            'completed' => 0,
            'approved' => 0,
        ];
        $priorityStats = [
            'high' => 0,
            'medium' => 0,
            'low' => 0,
        ];

        // Créer des produits pour chaque demande de réparation
        foreach ($repairRequests as $repairRequest) {
            $productCount = fake()->numberBetween(1, 5); // Entre 1 et 5 produits par demande

            // Sélectionner des produits aléatoirement (éviter les doublons)
            $selectedProducts = $products->random($productCount);

            foreach ($selectedProducts as $product) {
                // Éviter les doublons pour la même demande de réparation
                if (RepairRequestProduct::where('repair_request_id', $repairRequest->id)
                                      ->where('product_id', $product->id)
                                      ->exists()) {
                    continue;
                }

                $quantity = fake()->numberBetween(1, 10);
                $unitPrice = $product->price; // Utiliser le prix du produit
                $totalPrice = $quantity * $unitPrice;
                $priority = fake()->randomElement(['high', 'medium', 'low']);

                // Déterminer le statut du workflow (80% avec statut, 20% pending)
                $workflowChance = fake()->randomFloat(0, 0, 1);

                $repairRequestProduct = RepairRequestProduct::create([
                    'repair_request_id' => $repairRequest->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'priority' => $priority,
                    'note' => $this->generateNote($priority, $product->name),
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);

                // Appliquer le workflow selon les probabilités
                if ($workflowChance > 0.2) { // 80% chance d'avoir un statut
                    if ($workflowChance > 0.8) { // 20% approved
                        $this->applyWorkflowStatus($repairRequestProduct, 'approved', $bureauStaff, $mechanics);
                        $statusStats['approved']++;
                    } elseif ($workflowChance > 0.6) { // 20% completed
                        $this->applyWorkflowStatus($repairRequestProduct, 'completed', $bureauStaff, $mechanics);
                        $statusStats['completed']++;
                    } elseif ($workflowChance > 0.4) { // 20% invoiced
                        $this->applyWorkflowStatus($repairRequestProduct, 'invoiced', $bureauStaff, $mechanics);
                        $statusStats['invoiced']++;
                    } else { // 20% pending
                        $statusStats['pending']++;
                    }
                } else { // 20% pending
                    $statusStats['pending']++;
                }

                $priorityStats[$priority]++;
                $totalCreated++;
            }
        }

        // Créer quelques cas spéciaux pour les tests
        $this->createSpecialCases($repairRequests, $products, $bureauStaff, $mechanics, $totalCreated, $statusStats, $priorityStats);

        // Affichage des statistiques
        $this->displayStatistics($totalCreated, $statusStats, $priorityStats);
    }

    /**
     * Génère une note contextuelle selon la priorité et le produit.
     */
    private function generateNote(string $priority, string $productName): ?string
    {
        $notes = [
            'high' => [
                "URGENT - $productName nécessaire immédiatement",
                "Arrêt de production - $productName critique",
                "Sécurité compromise sans $productName",
                "Client en attente - $productName prioritaire",
            ],
            'medium' => [
                "Maintenance programmée - $productName à remplacer",
                "Usure normale - $productName à prévoir",
                "Stock de sécurité - $productName recommandé",
                null, // Pas de note parfois
            ],
            'low' => [
                "Maintenance préventive - $productName optionnel",
                "Amélioration performance - $productName à considérer",
                "Pièce de rechange - $productName en stock",
                null, // Pas de note parfois
            ],
        ];

        return fake()->randomElement($notes[$priority]);
    }

    /**
     * Applique un statut de workflow à un produit de demande de réparation.
     */
    private function applyWorkflowStatus(RepairRequestProduct $repairRequestProduct, string $status, $bureauStaff, $mechanics): void
    {
        $baseDate = fake()->dateTimeBetween('-30 days', '-1 day');

        switch ($status) {
            case 'approved':
                $invoicedDate = $baseDate;
                $completedDate = fake()->dateTimeBetween($invoicedDate, '-1 day');
                $approvedDate = fake()->dateTimeBetween($completedDate, 'now');

                $repairRequestProduct->update([
                    'invoiced_by' => $bureauStaff->random()->id,
                    'invoiced_at' => $invoicedDate,
                    'completed_by' => $mechanics->random()->id,
                    'completed_at' => $completedDate,
                    'approved_at' => $approvedDate,
                ]);
                break;

            case 'completed':
                $invoicedDate = $baseDate;
                $completedDate = fake()->dateTimeBetween($invoicedDate, 'now');

                $repairRequestProduct->update([
                    'invoiced_by' => $bureauStaff->random()->id,
                    'invoiced_at' => $invoicedDate,
                    'completed_by' => $mechanics->random()->id,
                    'completed_at' => $completedDate,
                ]);
                break;

            case 'invoiced':
                $repairRequestProduct->update([
                    'invoiced_by' => $bureauStaff->random()->id,
                    'invoiced_at' => $baseDate,
                ]);
                break;
        }
    }

    /**
     * Crée des cas spéciaux pour les tests.
     */
    private function createSpecialCases($repairRequests, $products, $bureauStaff, $mechanics, &$totalCreated, &$statusStats, &$priorityStats): void
    {
        if ($repairRequests->count() > 0 && $products->count() > 0) {
            // Cas 1: Produit très cher avec priorité haute
            $expensiveCase = RepairRequestProduct::create([
                'repair_request_id' => $repairRequests->random()->id,
                'product_id' => $products->random()->id,
                'quantity' => 1,
                'priority' => 'high',
                'note' => 'CRITIQUE - Pièce coûteuse nécessaire pour remise en service',
                'unit_price' => 2500.00,
                'total_price' => 2500.00,
                'invoiced_by' => $bureauStaff->random()->id,
                'invoiced_at' => now()->subDays(2),
            ]);

            // Cas 2: Grosse quantité de consommables
            $consumableCase = RepairRequestProduct::create([
                'repair_request_id' => $repairRequests->random()->id,
                'product_id' => $products->random()->id,
                'quantity' => 50,
                'priority' => 'low',
                'note' => 'Réapprovisionnement stock consommables maintenance',
                'unit_price' => 5.50,
                'total_price' => 275.00,
            ]);

            // Cas 3: Workflow complet (approved)
            $approvedCase = RepairRequestProduct::create([
                'repair_request_id' => $repairRequests->random()->id,
                'product_id' => $products->random()->id,
                'quantity' => 2,
                'priority' => 'medium',
                'note' => 'Remplacement standard - Procédure complète',
                'unit_price' => 150.00,
                'total_price' => 300.00,
                'invoiced_by' => $bureauStaff->random()->id,
                'invoiced_at' => now()->subDays(5),
                'completed_by' => $mechanics->random()->id,
                'completed_at' => now()->subDays(2),
                'approved_at' => now()->subDays(1),
            ]);

            $totalCreated += 3;
            $statusStats['invoiced']++;
            $statusStats['pending']++;
            $statusStats['approved']++;
            $priorityStats['high']++;
            $priorityStats['low']++;
            $priorityStats['medium']++;
        }
    }

    /**
     * Affiche les statistiques de création.
     */
    private function displayStatistics(int $totalCreated, array $statusStats, array $priorityStats): void
    {
        $this->command->info("\n📊 Statistiques des produits de demandes de réparation créés:");
        $this->command->info("• Total créé: $totalCreated");

        $this->command->info("\n📋 Par statut:");
        foreach ($statusStats as $status => $count) {
            $percentage = $totalCreated > 0 ? round(($count / $totalCreated) * 100, 1) : 0;
            $statusDisplay = match($status) {
                'pending' => 'En attente',
                'invoiced' => 'Facturé',
                'completed' => 'Terminé',
                'approved' => 'Approuvé',
            };
            $this->command->info("  - $statusDisplay: $count ($percentage%)");
        }

        $this->command->info("\n🎯 Par priorité:");
        foreach ($priorityStats as $priority => $count) {
            $percentage = $totalCreated > 0 ? round(($count / $totalCreated) * 100, 1) : 0;
            $priorityDisplay = match($priority) {
                'high' => 'Haute',
                'medium' => 'Moyenne',
                'low' => 'Basse',
            };
            $this->command->info("  - $priorityDisplay: $count ($percentage%)");
        }

        // Calcul des montants
        $totalValue = RepairRequestProduct::sum('total_price');
        $averageValue = RepairRequestProduct::avg('total_price');
        $maxValue = RepairRequestProduct::max('total_price');

        $this->command->info("\n💰 Valeurs financières:");
        $this->command->info("  - Valeur totale: " . number_format($totalValue, 2) . " €");
        $this->command->info("  - Valeur moyenne: " . number_format($averageValue, 2) . " €");
        $this->command->info("  - Valeur maximale: " . number_format($maxValue, 2) . " €");

        $this->command->info("\n✅ Seeder RepairRequestProduct terminé avec succès!");
    }
}
