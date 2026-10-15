<?php

namespace Database\Factories;

use App\Models\CrewMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrewMessage>
 */
class CrewMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_name' => fake()->name(),
            'recipient_name' => fake()->name(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'read_at' => null,
        ];
    }
}
