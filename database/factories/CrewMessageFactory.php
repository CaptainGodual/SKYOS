<?php

namespace Database\Factories;

use App\Models\CrewMessage;
use App\Models\User;
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
            'sender_id' => User::factory(),
            'sender_name' => fake()->name(),
            'recipient_id' => User::factory(),
            'recipient_name' => fake()->name(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'read_at' => null,
        ];
    }

    /**
     * Address the message to a specific crew member.
     */
    public function recipient(User $user): static
    {
        return $this->state(fn (): array => [
            'recipient_id' => $user->id,
            'recipient_name' => $user->name,
        ]);
    }

    /**
     * Send the message from a specific crew member.
     */
    public function sender(User $user): static
    {
        return $this->state(fn (): array => [
            'sender_id' => $user->id,
            'sender_name' => $user->name,
        ]);
    }

    /**
     * Indicate that the recipient has already opened the message.
     */
    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }
}
