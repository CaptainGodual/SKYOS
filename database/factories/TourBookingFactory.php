<?php

namespace Database\Factories;

use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TourBooking>
 */
class TourBookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tour_id' => Tour::factory(),
            'pilot_id' => User::factory(),
            'status' => fake()->randomElement(['booked', 'in_progress', 'completed', 'cancelled']),
            'booked_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'completed_at' => null,
        ];
    }
}
