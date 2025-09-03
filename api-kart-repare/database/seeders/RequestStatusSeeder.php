<?php

namespace Database\Seeders;

use App\Models\RequestStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RequestStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Définir les statuts de base avec leurs couleurs
        $statuses = [
            [
                'name' => 'Brouillon',
                'hex_color' => '#adb5bd',
                'is_final' => false,
            ],
            [
                'name' => 'En attente',
                'hex_color' => '#ffc107',
                'is_final' => false,
            ],
            [
                'name' => 'En cours',
                'hex_color' => '#007bff',
                'is_final' => false,
            ],
            [
                'name' => 'En révision',
                'hex_color' => '#fd7e14',
                'is_final' => false,
            ],
            [
                'name' => 'Approuvé',
                'hex_color' => '#198754',
                'is_final' => false,
            ],
            [
                'name' => 'Suspendu',
                'hex_color' => '#e83e8c',
                'is_final' => false,
            ],
            [
                'name' => 'Terminé',
                'hex_color' => '#198754',
                'is_final' => true,
            ],
            [
                'name' => 'Validé',
                'hex_color' => '#20c997',
                'is_final' => true,
            ],
            [
                'name' => 'Rejeté',
                'hex_color' => '#dc3545',
                'is_final' => true,
            ],
            [
                'name' => 'Annulé',
                'hex_color' => '#6c757d',
                'is_final' => true,
            ],
        ];

        $createdStatuses = [];

        foreach ($statuses as $statusData) {
            $status = RequestStatus::create($statusData);
            $createdStatuses[] = $status;

            echo "Créé le statut: {$status->name} ({$status->hex_color})" .
                 ($status->is_final ? " [FINAL]" : "") . "\n";
        }

        echo "\n=== Statistiques des statuts créés ===\n";
        echo "Total: " . count($createdStatuses) . " statuts\n";
        echo "Statuts finaux: " . collect($createdStatuses)->where('is_final', true)->count() . "\n";
        echo "Statuts non-finaux: " . collect($createdStatuses)->where('is_final', false)->count() . "\n";

        // Grouper par couleur
        $colorGroups = collect($createdStatuses)->groupBy('hex_color');
        echo "\nRépartition des couleurs:\n";
        foreach ($colorGroups as $color => $statuses) {
            echo "- {$color}: " . $statuses->count() . " statut(s)\n";
        }

        echo "\nStatuts de demande créés avec succès !\n";
    }
}
