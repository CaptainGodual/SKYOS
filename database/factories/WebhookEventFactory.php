<?php

namespace Database\Factories;

use App\Models\Flight;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $event = fake()->randomElement(['Flight took off', 'Flight canceled', 'Flight completed']);

        return [
            'flight_id' => Flight::factory(),
            'event_type' => $event,
            'status' => 'Awaiting configuration',
            'payload' => ['event' => $event],
            'delivered_at' => null,
        ];
    }
}
