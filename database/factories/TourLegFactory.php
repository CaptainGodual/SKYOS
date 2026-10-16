<?php

namespace Database\Factories;

use App\Models\Airport;
use App\Models\Tour;
use App\Models\TourLeg;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TourLeg>
 */
class TourLegFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tour_id' => Tour::factory(),
            'leg_number' => fake()->unique()->numberBetween(1, 100),
            'departure_airport_id' => Airport::factory(),
            'arrival_airport_id' => Airport::factory(),
            'distance_nm' => fake()->numberBetween(100, 1500),
            'estimated_minutes' => fake()->numberBetween(45, 480),
        ];
    }
}
