<?php

namespace Database\Factories;

use App\Models\Hub;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hub>
 */
class HubFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('???'),
            'name' => fake()->company(),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'is_active' => true,
        ];
    }
}
