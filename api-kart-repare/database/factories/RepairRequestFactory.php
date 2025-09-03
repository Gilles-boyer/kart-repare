<?php

namespace Database\Factories;

use App\Models\RepairRequest;
use App\Models\Kart;
use App\Models\RequestStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RepairRequest>
 */
class RepairRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RepairRequest::class;

    /**
     * Common repair titles based on priority.
     */
    private static array $repairTitles = [
        'low' => [
            'Nettoyage général du kart',
            'Vérification des niveaux',
            'Inspection visuelle',
            'Remplacement des autocollants',
            'Lubrification des chaînes',
        ],
        'medium' => [
            'Changement des pneus',
            'Révision du moteur',
            'Réglage des freins',
            'Remplacement des bougies',
            'Vidange huile moteur',
            'Ajustement de la direction',
        ],
        'high' => [
            'Réparation moteur cassé',
            'Remplacement système de freinage',
            'Réparation châssis endommagé',
            'Problème de sécurité critique',
            'Panne électrique majeure',
        ],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $priority = fake()->randomElement(['low', 'medium', 'high']);
        $title = fake()->randomElement(static::$repairTitles[$priority]);

        $createdAt = fake()->dateTimeBetween('-3 months', 'now');
        $estimatedCompletion = fake()->dateTimeBetween($createdAt, '+1 month');

        // Randomly decide if the repair is started/completed
        $isStarted = fake()->boolean(60); // 60% chance of being started
        $isCompleted = $isStarted ? fake()->boolean(40) : false; // 40% chance of being completed if started

        $startedAt = $isStarted ? fake()->dateTimeBetween($createdAt, 'now') : null;
        $completedAt = $isCompleted ? fake()->dateTimeBetween($startedAt, 'now') : null;

        $estimatedCost = fake()->randomFloat(2, 50, 1000);
        $actualCost = $isCompleted ? fake()->randomFloat(2, $estimatedCost * 0.8, $estimatedCost * 1.3) : 0;

        return [
            'kart_id' => Kart::factory(),
            'title' => $title,
            'description' => fake()->optional(0.8)->paragraph(),
            'status_id' => RequestStatus::factory(),
            'priority' => $priority,
            'created_by' => User::factory(),
            'assigned_to' => fake()->optional(0.7)->randomElement([
                User::factory(),
                null
            ]),
            'estimated_cost' => $estimatedCost,
            'actual_cost' => $actualCost,
            'estimated_completion' => $estimatedCompletion,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'created_at' => $createdAt,
        ];
    }

    /**
     * Create a low priority repair request.
     */
    public function lowPriority(): static
    {
        return $this->state(function (array $attributes) {
            $title = fake()->randomElement(static::$repairTitles['low']);
            return [
                'priority' => 'low',
                'title' => $title,
                'estimated_cost' => fake()->randomFloat(2, 20, 200),
            ];
        });
    }

    /**
     * Create a medium priority repair request.
     */
    public function mediumPriority(): static
    {
        return $this->state(function (array $attributes) {
            $title = fake()->randomElement(static::$repairTitles['medium']);
            return [
                'priority' => 'medium',
                'title' => $title,
                'estimated_cost' => fake()->randomFloat(2, 100, 500),
            ];
        });
    }

    /**
     * Create a high priority repair request.
     */
    public function highPriority(): static
    {
        return $this->state(function (array $attributes) {
            $title = fake()->randomElement(static::$repairTitles['high']);
            return [
                'priority' => 'high',
                'title' => $title,
                'estimated_cost' => fake()->randomFloat(2, 300, 1500),
            ];
        });
    }

    /**
     * Create a completed repair request.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = fake()->dateTimeBetween('-2 months', '-1 week');
            $completedAt = fake()->dateTimeBetween($startedAt, 'now');

            return [
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'actual_cost' => fake()->randomFloat(2, 50, 1000),
            ];
        });
    }

    /**
     * Create an active (started but not completed) repair request.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'started_at' => fake()->dateTimeBetween('-1 month', 'now'),
                'completed_at' => null,
                'actual_cost' => 0,
            ];
        });
    }

    /**
     * Create a pending (not started) repair request.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'started_at' => null,
                'completed_at' => null,
                'actual_cost' => 0,
            ];
        });
    }

    /**
     * Create an overdue repair request.
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'estimated_completion' => fake()->dateTimeBetween('-1 month', '-1 day'),
                'started_at' => fake()->optional(0.7)->dateTimeBetween('-2 months', '-1 week'),
                'completed_at' => null,
                'actual_cost' => 0,
            ];
        });
    }

    /**
     * Create a repair request with over-budget cost.
     */
    public function overBudget(): static
    {
        return $this->state(function (array $attributes) {
            $estimatedCost = fake()->randomFloat(2, 200, 800);
            $actualCost = $estimatedCost * fake()->randomFloat(2, 1.2, 1.8); // 20-80% over budget

            return [
                'estimated_cost' => $estimatedCost,
                'actual_cost' => $actualCost,
                'started_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
                'completed_at' => fake()->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    /**
     * Create a repair request under budget.
     */
    public function underBudget(): static
    {
        return $this->state(function (array $attributes) {
            $estimatedCost = fake()->randomFloat(2, 200, 800);
            $actualCost = $estimatedCost * fake()->randomFloat(2, 0.6, 0.9); // 10-40% under budget

            return [
                'estimated_cost' => $estimatedCost,
                'actual_cost' => $actualCost,
                'started_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
                'completed_at' => fake()->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    /**
     * Create an urgent repair request.
     */
    public function urgent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'priority' => 'high',
                'title' => fake()->randomElement([
                    'URGENT - Panne moteur critique',
                    'URGENT - Freins défaillants',
                    'URGENT - Problème sécurité',
                    'URGENT - Châssis endommagé',
                ]),
                'estimated_completion' => now()->addDays(1),
                'description' => 'Réparation urgente requise pour des raisons de sécurité.',
                'estimated_cost' => fake()->randomFloat(2, 400, 1200),
            ];
        });
    }

    /**
     * Create a maintenance repair request.
     */
    public function maintenance(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'priority' => 'low',
                'title' => fake()->randomElement([
                    'Maintenance préventive',
                    'Nettoyage et inspection',
                    'Vérification niveaux',
                    'Lubrification chaînes',
                    'Contrôle général',
                ]),
                'estimated_completion' => now()->addWeeks(2),
                'estimated_cost' => fake()->randomFloat(2, 30, 150),
            ];
        });
    }

    /**
     * Create a repair request in various realistic states for testing.
     */
    public function realistic(): static
    {
        return $this->state(function (array $attributes) {
            $scenarios = [
                // Demande standard complète
                [
                    'priority' => 'medium',
                    'started_at' => fake()->dateTimeBetween('-2 weeks', '-1 week'),
                    'completed_at' => fake()->dateTimeBetween('-1 week', 'now'),
                    'actual_cost' => fake()->randomFloat(2, 80, 300),
                ],
                // Demande en cours
                [
                    'priority' => fake()->randomElement(['medium', 'high']),
                    'started_at' => fake()->dateTimeBetween('-1 week', 'now'),
                    'completed_at' => null,
                    'actual_cost' => 0,
                ],
                // Demande en attente
                [
                    'priority' => fake()->randomElement(['low', 'medium']),
                    'started_at' => null,
                    'completed_at' => null,
                    'actual_cost' => 0,
                ],
                // Demande urgente en retard
                [
                    'priority' => 'high',
                    'estimated_completion' => fake()->dateTimeBetween('-1 week', '-1 day'),
                    'started_at' => fake()->optional(0.8)->dateTimeBetween('-2 weeks', '-1 week'),
                    'completed_at' => null,
                    'actual_cost' => 0,
                ],
            ];

            return fake()->randomElement($scenarios);
        });
    }
}
