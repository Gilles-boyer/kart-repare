<?php

namespace Database\Factories;

use App\Models\RepairRequestProduct;
use App\Models\RepairRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RepairRequestProduct>
 */
class RepairRequestProductFactory extends Factory
{
    protected $model = RepairRequestProduct::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->randomFloat(2, 5.00, 500.00);
        $totalPrice = $quantity * $unitPrice;

        return [
            'repair_request_id' => RepairRequest::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'priority' => fake()->randomElement(['high', 'medium', 'low']),
            'note' => fake()->optional(0.6)->text(200),
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'invoiced_by' => null,
            'invoiced_at' => null,
            'completed_by' => null,
            'completed_at' => null,
            'approved_at' => null,
        ];
    }

    /**
     * Indicate that the product request has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
            'note' => 'Pièce critique nécessaire en urgence - Arrêt de production',
        ]);
    }

    /**
     * Indicate that the product request has medium priority.
     */
    public function mediumPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'medium',
        ]);
    }

    /**
     * Indicate that the product request has low priority.
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'low',
            'note' => 'Maintenance préventive - Pas d\'urgence',
        ]);
    }

    /**
     * Indicate that the product request has been invoiced.
     */
    public function invoiced(): static
    {
        return $this->state(function (array $attributes) {
            $invoicedDate = fake()->dateTimeBetween('-30 days', 'now');

            return [
                'invoiced_by' => User::factory()->bureauStaff(),
                'invoiced_at' => $invoicedDate,
            ];
        });
    }

    /**
     * Indicate that the product request has been completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $invoicedDate = fake()->dateTimeBetween('-30 days', '-1 days');
            $completedDate = fake()->dateTimeBetween($invoicedDate, 'now');

            return [
                'invoiced_by' => User::factory()->bureauStaff(),
                'invoiced_at' => $invoicedDate,
                'completed_by' => User::factory()->mechanic(),
                'completed_at' => $completedDate,
            ];
        });
    }

    /**
     * Indicate that the product request has been approved.
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            $invoicedDate = fake()->dateTimeBetween('-30 days', '-5 days');
            $completedDate = fake()->dateTimeBetween($invoicedDate, '-2 days');
            $approvedDate = fake()->dateTimeBetween($completedDate, 'now');

            return [
                'invoiced_by' => User::factory()->bureauStaff(),
                'invoiced_at' => $invoicedDate,
                'completed_by' => User::factory()->mechanic(),
                'completed_at' => $completedDate,
                'approved_at' => $approvedDate,
            ];
        });
    }

    /**
     * Create a product request for engine parts (higher prices).
     */
    public function enginePart(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->numberBetween(1, 3);
            $unitPrice = fake()->randomFloat(2, 200.00, 1500.00);

            return [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
                'priority' => fake()->randomElement(['high', 'medium']),
                'note' => 'Pièce moteur - Vérifier compatibilité avec le modèle',
            ];
        });
    }

    /**
     * Create a product request for consumables (lower prices, higher quantities).
     */
    public function consumable(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->numberBetween(5, 50);
            $unitPrice = fake()->randomFloat(2, 2.00, 25.00);

            return [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
                'priority' => fake()->randomElement(['medium', 'low']),
                'note' => 'Consommable - Stock de sécurité',
            ];
        });
    }

    /**
     * Create a product request for safety equipment.
     */
    public function safetyEquipment(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->numberBetween(1, 5);
            $unitPrice = fake()->randomFloat(2, 50.00, 300.00);

            return [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
                'priority' => 'high',
                'note' => 'Équipement de sécurité - Conformité obligatoire',
            ];
        });
    }

    /**
     * Create a product request with custom quantity and price ranges.
     */
    public function withPriceRange(float $minPrice, float $maxPrice, int $minQuantity = 1, int $maxQuantity = 10): static
    {
        return $this->state(function (array $attributes) use ($minPrice, $maxPrice, $minQuantity, $maxQuantity) {
            $quantity = fake()->numberBetween($minQuantity, $maxQuantity);
            $unitPrice = fake()->randomFloat(2, $minPrice, $maxPrice);

            return [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
            ];
        });
    }

    /**
     * Create a product request with specific workflow state.
     */
    public function withWorkflowState(string $state = 'pending'): static
    {
        return match($state) {
            'invoiced' => $this->invoiced(),
            'completed' => $this->completed(),
            'approved' => $this->approved(),
            default => $this->state([]),
        };
    }

    /**
     * Create a product request for a specific repair request.
     */
    public function forRepairRequest(RepairRequest|int $repairRequest): static
    {
        $repairRequestId = is_int($repairRequest) ? $repairRequest : $repairRequest->id;

        return $this->state(fn (array $attributes) => [
            'repair_request_id' => $repairRequestId,
        ]);
    }

    /**
     * Create a product request for a specific product.
     */
    public function forProduct(Product|int $product): static
    {
        $productId = is_int($product) ? $product : $product->id;

        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
        ]);
    }

    /**
     * Create an urgent product request.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
            'note' => 'URGENT - Intervention immédiate requise - Kart hors service',
        ]);
    }

    /**
     * Create a maintenance product request.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'low',
            'note' => 'Maintenance préventive programmée selon planning d\'entretien',
        ]);
    }

    /**
     * Create a realistic product request for testing.
     */
    public function realistic(): static
    {
        $scenarios = [
            'enginePart',
            'consumable',
            'safetyEquipment',
            'urgent',
            'maintenance'
        ];

        $scenario = fake()->randomElement($scenarios);
        return $this->$scenario();
    }
}
