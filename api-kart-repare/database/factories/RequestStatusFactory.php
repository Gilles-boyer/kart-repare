<?php

namespace Database\Factories;

use App\Models\RequestStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RequestStatus>
 */
class RequestStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RequestStatus::class;

    /**
     * Status names with their typical colors.
     */
    private static array $statusData = [
        ['name' => 'En attente', 'color' => '#ffc107', 'is_final' => false],
        ['name' => 'En cours', 'color' => '#007bff', 'is_final' => false],
        ['name' => 'En révision', 'color' => '#fd7e14', 'is_final' => false],
        ['name' => 'Approuvé', 'color' => '#198754', 'is_final' => false],
        ['name' => 'Rejeté', 'color' => '#dc3545', 'is_final' => true],
        ['name' => 'Terminé', 'color' => '#198754', 'is_final' => true],
        ['name' => 'Annulé', 'color' => '#6c757d', 'is_final' => true],
        ['name' => 'Suspendu', 'color' => '#e83e8c', 'is_final' => false],
        ['name' => 'Validé', 'color' => '#20c997', 'is_final' => true],
        ['name' => 'Brouillon', 'color' => '#adb5bd', 'is_final' => false],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statusData = fake()->randomElement(static::$statusData);

        // Generate unique name for testing
        $uniqueName = $statusData['name'] . ' ' . fake()->unique()->numberBetween(1, 9999);

        return [
            'name' => $uniqueName,
            'hex_color' => $statusData['color'],
            'is_final' => $statusData['is_final'],
        ];
    }

    /**
     * Create a custom status with specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(function (array $attributes) use ($name) {
            return [
                'name' => $name,
            ];
        });
    }

    /**
     * Create a final status.
     */
    public function final(): static
    {
        $finalStatuses = array_filter(static::$statusData, fn($status) => $status['is_final']);
        $statusData = fake()->randomElement($finalStatuses);

        return $this->state(function (array $attributes) use ($statusData) {
            return [
                'name' => $statusData['name'],
                'hex_color' => $statusData['color'],
                'is_final' => true,
            ];
        });
    }

    /**
     * Create a non-final status.
     */
    public function notFinal(): static
    {
        $nonFinalStatuses = array_filter(static::$statusData, fn($status) => !$status['is_final']);
        $statusData = fake()->randomElement($nonFinalStatuses);

        return $this->state(function (array $attributes) use ($statusData) {
            return [
                'name' => $statusData['name'],
                'hex_color' => $statusData['color'],
                'is_final' => false,
            ];
        });
    }

    /**
     * Create a status with specific color.
     */
    public function withColor(string $hexColor): static
    {
        return $this->state(function (array $attributes) use ($hexColor) {
            return [
                'hex_color' => $hexColor,
            ];
        });
    }

    /**
     * Create a pending status.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'En attente',
                'hex_color' => '#ffc107',
                'is_final' => false,
            ];
        });
    }

    /**
     * Create an approved status.
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Approuvé',
                'hex_color' => '#198754',
                'is_final' => false,
            ];
        });
    }

    /**
     * Create a completed status.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Terminé',
                'hex_color' => '#198754',
                'is_final' => true,
            ];
        });
    }

    /**
     * Create a rejected status.
     */
    public function rejected(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Rejeté',
                'hex_color' => '#dc3545',
                'is_final' => true,
            ];
        });
    }
}
