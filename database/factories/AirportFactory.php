<?php

namespace Database\Factories;

use App\Models\Airport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Airport>
 */
class AirportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identifier' => fake()->unique()->bothify('??##'),
            'iata_code' => fake()->optional()->bothify('???'),
            'icao_code' => fake()->optional()->bothify('####'),
            'name' => fake()->company().' Airport',
            'type' => fake()->randomElement(['small_airport', 'medium_airport', 'large_airport']),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'elevation' => fake()->optional()->numberBetween(0, 10000),
            'country_code' => fake()->optional()->countryCode(),
            'region_code' => fake()->optional()->bothify('??-#'),
            'municipality' => fake()->optional()->city(),
            'time_zone' => fake()->optional()->timezone(),
            'is_hub' => false,
        ];
    }
}
