<?php

namespace Database\Factories;

use App\Models\Airport;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tour>
 */
class TourFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Transatlantic Crossing',
                'European Explorer',
                'Pacific Rim Circuit',
                'South American Adventure',
                'African Safari Skies',
                'Asian Grand Tour',
                'Oceania Island Hopper',
                'North American Cross-Country',
            ]).' '.fake()->word(),
            'description' => fake()->paragraph(2),
            'origin_airport_id' => Airport::factory(),
            'destination_airport_id' => Airport::factory(),
            'total_distance_nm' => fake()->numberBetween(500, 5000),
            'estimated_duration_minutes' => fake()->numberBetween(120, 1200),
            'price_cents' => fake()->numberBetween(5000, 50000),
            'is_active' => true,
        ];
    }
}
