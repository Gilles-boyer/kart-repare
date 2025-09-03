<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unities = ['piece', 'hours', 'liters', 'kg'];
        $unity = $this->faker->randomElement($unities);
        $minStock = $this->faker->numberBetween(5, 20);
        $inStock = $this->faker->numberBetween(0, $minStock * 4);

        // Produits typiques pour l'entretien de karts
        $productTypes = [
            'piece' => [
                'names' => ['Bougie d\'allumage', 'Filtre à air', 'Chaîne de transmission', 'Pignon moteur', 'Couronne', 'Plaquettes de frein', 'Disque de frein', 'Pneu avant', 'Pneu arrière', 'Jante aluminium'],
                'refs' => ['BG-', 'FA-', 'CHN-', 'PGN-', 'CRN-', 'PLQ-', 'DSQ-', 'PNV-', 'PNR-', 'JNT-'],
                'prices' => [15, 85, 45, 25, 35, 60, 120, 180, 200, 250]
            ],
            'liters' => [
                'names' => ['Huile moteur 2T', 'Essence sans plomb 98', 'Liquide de frein', 'Dégraissant chaîne', 'Huile de boîte'],
                'refs' => ['HM2T-', 'ESS98-', 'LDF-', 'DGR-', 'HBT-'],
                'prices' => [25, 15, 12, 8, 30]
            ],
            'hours' => [
                'names' => ['Main d\'œuvre révision', 'Main d\'œuvre réparation', 'Main d\'œuvre diagnostic', 'Main d\'œuvre montage'],
                'refs' => ['MO-REV-', 'MO-REP-', 'MO-DIAG-', 'MO-MONT-'],
                'prices' => [45, 50, 35, 40]
            ],
            'kg' => [
                'names' => ['Graisse haute température', 'Pâte de montage', 'Produit d\'étanchéité'],
                'refs' => ['GRS-HT-', 'PAT-MT-', 'ETN-'],
                'prices' => [18, 22, 15]
            ]
        ];

        $typeData = $productTypes[$unity];
        $index = $this->faker->numberBetween(0, count($typeData['names']) - 1);

        return [
            'name' => $typeData['names'][$index],
            'description' => $this->generateDescription($typeData['names'][$index], $unity),
            'ref' => $typeData['refs'][$index] . strtoupper($this->faker->bothify('##??')),
            'price' => $typeData['prices'][$index] + $this->faker->randomFloat(2, -5, 10),
            'image' => 'products/' . $this->faker->slug() . '.jpg',
            'in_stock' => $inStock,
            'unity' => $unity,
            'min_stock' => $minStock,
        ];
    }

    private function generateDescription(string $name, string $unity): string
    {
        $descriptions = [
            'piece' => [
                'Pièce de rechange de qualité professionnelle',
                'Compatible avec la plupart des modèles de kart',
                'Conforme aux normes de sécurité en vigueur',
                'Installation facile avec outillage standard'
            ],
            'liters' => [
                'Produit haute performance pour karts de compétition',
                'Formulation spécialement adaptée aux moteurs 2 temps',
                'Respect de l\'environnement et des normes',
                'Conditionnement professionnel'
            ],
            'hours' => [
                'Service réalisé par nos techniciens qualifiés',
                'Intervention selon les standards constructeur',
                'Garantie sur la prestation effectuée',
                'Rapport détaillé des opérations réalisées'
            ],
            'kg' => [
                'Produit chimique haute qualité',
                'Application professionnelle recommandée',
                'Stockage selon conditions optimales',
                'Fiche de sécurité disponible'
            ]
        ];

        return $name . '. ' . $this->faker->randomElement($descriptions[$unity]) . '.';
    }

    /**
     * Product with low stock
     */
    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'in_stock' => $this->faker->numberBetween(1, 3), // Jamais 0
                'min_stock' => $this->faker->numberBetween(5, 10),
            ];
        });
    }

    /**
     * Product out of stock
     */
    public function outOfStock(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'in_stock' => 0,
                'min_stock' => $this->faker->numberBetween(5, 15),
            ];
        });
    }

    /**
     * Product with high stock
     */
    public function highStock(): static
    {
        return $this->state(function (array $attributes) {
            $minStock = $this->faker->numberBetween(5, 10);
            return [
                'in_stock' => $this->faker->numberBetween($minStock * 3, $minStock * 6),
                'min_stock' => $minStock,
            ];
        });
    }

    /**
     * Expensive product
     */
    public function expensive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'price' => $this->faker->randomFloat(2, 200, 500),
            ];
        });
    }

    /**
     * Cheap product
     */
    public function cheap(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'price' => $this->faker->randomFloat(2, 5, 25),
            ];
        });
    }
}
