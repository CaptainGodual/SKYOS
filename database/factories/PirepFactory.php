<?php

namespace Database\Factories;

use App\Models\Pirep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pirep>
 */
class PirepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pilot_name' => fake()->name(),
            'flight_number' => 'SKY'.fake()->unique()->numberBetween(100, 999),
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => fake()->numberBetween(45, 480),
            'landing_rate' => fake()->numberBetween(60, 240),
            'payout_cents' => fake()->numberBetween(5_000, 50_000),
            'remarks' => fake()->sentence(),
            'status' => 'Pending review',
        ];
    }
}
