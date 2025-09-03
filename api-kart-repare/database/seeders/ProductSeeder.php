<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "Création des produits...\n";

        // Créer différents types de produits
        $products = [
            // Pièces
            Product::factory()->count(15)->create(['unity' => 'piece']),
            // Liquides
            Product::factory()->count(8)->create(['unity' => 'liters']),
            // Services (heures)
            Product::factory()->count(5)->create(['unity' => 'hours']),
            // Produits en vrac (kg)
            Product::factory()->count(4)->create(['unity' => 'kg']),
        ];

        // Créer quelques produits avec des états spécifiques
        $specificProducts = [
            Product::factory()->lowStock()->count(3)->create(),
            Product::factory()->outOfStock()->count(2)->create(),
            Product::factory()->highStock()->count(5)->create(),
            Product::factory()->expensive()->count(2)->create(),
            Product::factory()->cheap()->count(3)->create(),
        ];

        // Compter les statistiques
        $totalProducts = Product::count();
        $byUnity = [
            'piece' => Product::where('unity', 'piece')->count(),
            'liters' => Product::where('unity', 'liters')->count(),
            'hours' => Product::where('unity', 'hours')->count(),
            'kg' => Product::where('unity', 'kg')->count(),
        ];

        $stockStats = [
            'in_stock' => Product::inStock()->count(),
            'out_of_stock' => Product::outOfStock()->count(),
            'needs_restock' => Product::whereRaw('in_stock <= min_stock')->count(),
        ];

        $priceStats = [
            'min_price' => Product::min('price'),
            'max_price' => Product::max('price'),
            'avg_price' => Product::avg('price'),
            'total_value' => Product::selectRaw('SUM(price * in_stock) as total')->first()->total,
        ];

        echo "\n=== Statistiques des produits créés ===\n";
        echo "Total: {$totalProducts} produits\n\n";

        echo "Par unité:\n";
        foreach ($byUnity as $unity => $count) {
            echo "- " . ucfirst($unity) . ": {$count}\n";
        }

        echo "\nÉtat des stocks:\n";
        echo "- En stock: {$stockStats['in_stock']}\n";
        echo "- Rupture: {$stockStats['out_of_stock']}\n";
        echo "- Réapprovisionnement nécessaire: {$stockStats['needs_restock']}\n";

        echo "\nPrix:\n";
        echo "- Prix minimum: " . number_format($priceStats['min_price'], 2) . " €\n";
        echo "- Prix maximum: " . number_format($priceStats['max_price'], 2) . " €\n";
        echo "- Prix moyen: " . number_format($priceStats['avg_price'], 2) . " €\n";
        echo "- Valeur totale du stock: " . number_format($priceStats['total_value'], 2) . " €\n";

        echo "\nProduits créés avec succès !\n";
    }
}
