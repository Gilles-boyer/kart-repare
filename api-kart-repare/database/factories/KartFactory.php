<?php

namespace Database\Factories;

use App\Models\Kart;
use App\Models\Pilot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kart>
 */
class KartFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Kart::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brands = ['Tony Kart', 'CRG', 'Birel ART', 'OTK', 'Kosmic', 'Exprit', 'Sodi', 'Intrepid', 'FA Kart', 'Praga'];
        $engineTypes = ['Rotax Max', 'IAME X30', 'Vortex ROK', 'TM KZ', 'Honda GX270', 'Briggs & Stratton'];
        $brand = fake()->randomElement($brands);

        return [
            'pilot_id' => Pilot::factory(),
            'brand' => $brand,
            'model' => fake()->randomElement(['Junior', 'Senior', 'Rookie', 'Victory', 'Racer', 'Storm', 'Thunder']),
            'chassis_number' => $this->generateChassisNumber($brand),
            'year' => fake()->numberBetween(2015, 2025),
            'engine_type' => fake()->randomElement($engineTypes),
            'is_active' => fake()->boolean(85), // 85% chance d'être actif
            'note' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Generate a realistic chassis number based on brand.
     */
    private function generateChassisNumber(string $brand): string
    {
        $prefixes = [
            'Tony Kart' => 'TK',
            'CRG' => 'CRG',
            'Birel ART' => 'BA',
            'OTK' => 'OTK',
            'Kosmic' => 'KOS',
            'Exprit' => 'EXP',
            'Sodi' => 'SOD',
            'Intrepid' => 'INT',
            'FA Kart' => 'FAK',
            'Praga' => 'PRG',
        ];

        $prefix = $prefixes[$brand] ?? 'KRT';
        $year = fake()->numberBetween(15, 25); // Années abrégées
        $serial = str_pad(fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$year}{$serial}";
    }

    /**
     * Indicate that the kart is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the kart is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the kart belongs to a specific pilot.
     */
    public function forPilot(Pilot $pilot): static
    {
        return $this->state(fn (array $attributes) => [
            'pilot_id' => $pilot->id,
        ]);
    }

    /**
     * Indicate that the kart has a specific brand.
     */
    public function ofBrand(string $brand): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => $brand,
            'chassis_number' => $this->generateChassisNumber($brand),
        ]);
    }

    /**
     * Indicate that the kart is from a specific year.
     */
    public function fromYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => $year,
        ]);
    }

    /**
     * Create a vintage kart (older than 10 years).
     */
    public function vintage(): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => fake()->numberBetween(2000, 2014),
            'is_active' => fake()->boolean(60), // Moins de chance d'être actif
        ]);
    }

    /**
     * Create a modern kart (recent years).
     */
    public function modern(): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => fake()->numberBetween(2020, 2025),
            'is_active' => true,
        ]);
    }
}
