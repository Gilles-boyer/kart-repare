<?php

namespace Database\Factories;

use App\Models\Pilot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pilot>
 */
class PilotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Pilot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dateOfBirth = fake()->dateTimeBetween('-60 years', '-10 years');
        $age = now()->diffInYears($dateOfBirth);
        $isMinor = $age < 18;

        return [
            'client_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => $dateOfBirth,
            'size_tshirt' => fake()->optional()->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
            'size_pants' => fake()->optional()->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
            'size_shoes' => fake()->optional()->numberBetween(35, 47),
            'size_glove' => fake()->optional()->randomElement(['XS', 'S', 'M', 'L', 'XL']),
            'size_suit' => fake()->optional()->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'is_minor' => $isMinor,
            'note' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the pilot is a minor.
     */
    public function minor(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_of_birth' => fake()->dateTimeBetween('-17 years', '-10 years'),
            'is_minor' => true,
        ]);
    }

    /**
     * Indicate that the pilot is an adult.
     */
    public function adult(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'is_minor' => false,
        ]);
    }

    /**
     * Indicate that the pilot belongs to a specific client.
     */
    public function forClient(User $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
        ]);
    }
}
