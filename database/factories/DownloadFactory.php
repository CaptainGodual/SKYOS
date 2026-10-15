<?php

namespace Database\Factories;

use App\Models\Download;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Download>
 */
class DownloadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['Documents', 'Liveries', 'Tools']),
            'version' => 'v'.fake()->numberBetween(1, 9).'.'.fake()->numberBetween(0, 9),
            'file_path' => '/downloads/'.fake()->slug().'.zip',
            'size_bytes' => fake()->numberBetween(500_000, 2_000_000_000),
            'description' => fake()->sentence(),
        ];
    }
}
