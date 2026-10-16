<?php

namespace Database\Factories;

use App\Models\FlightExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlightExpense>
 */
class FlightExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Ground Handling',
                'Landing Fee',
                'Fuel Surcharge',
                'Catering',
                'De-icing',
                'Navigation Fee',
                'Parking Fee',
                'Customs Fee',
            ]),
            'category' => $this->faker->randomElement([
                'Handling',
                'Landing',
                'Fuel',
                'Catering',
                'De-icing',
                'Navigation',
                'Parking',
                'Customs',
            ]),
            'description' => $this->faker->sentence(),
            'cost_cents' => $this->faker->numberBetween(5000, 50000),
            'is_active' => true,
        ];
    }
}
