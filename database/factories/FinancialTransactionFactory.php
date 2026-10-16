<?php

namespace Database\Factories;

use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialTransaction>
 */
class FinancialTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['revenue', 'expense']),
            'category' => $this->faker->randomElement(['Flight Revenue', 'Ground Handling', 'Landing Fee', 'Fuel', 'Catering']),
            'description' => $this->faker->sentence(),
            'amount_cents' => $this->faker->numberBetween(-50000, 50000),
            'reference_type' => $this->faker->optional()->randomElement(['pirep', 'manual']),
            'reference_id' => $this->faker->optional()->numberBetween(1, 100),
        ];
    }
}
