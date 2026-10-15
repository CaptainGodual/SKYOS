<?php

namespace Database\Factories;

use App\Models\Flight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Flight>
 */
class FlightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'flight_number' => 'SKY'.fake()->unique()->numberBetween(100, 999),
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'aircraft' => 'Airbus A320neo',
            'scheduled_at' => now()->addHour(),
            'estimated_minutes' => 60,
            'payout_cents' => 12500,
            'status' => 'Open',
            'acars_status' => 'Scheduled',
        ];
    }
}
