<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = ['client', 'bureau_staff', 'mechanic', 'admin'];

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement($roles),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'company' => fake()->optional()->company(),
            'is_active' => true,
            'last_login_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);
    }

    /**
     * Create a user with client role.
     */
    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'client',
            'company' => fake()->company(),
        ]);
    }

    /**
     * Create a user with bureau staff role.
     */
    public function bureauStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'bureau_staff',
            'company' => null,
        ]);
    }

    /**
     * Create a user with mechanic role.
     */
    public function mechanic(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'mechanic',
            'company' => null,
        ]);
    }

    /**
     * Create an inactive user.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
