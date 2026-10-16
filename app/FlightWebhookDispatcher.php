<?php

namespace App;

use App\Models\Flight;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;

class FlightWebhookDispatcher
{
    public function dispatch(Flight $flight, string $eventType): WebhookEvent
    {
        $payload = [
            'event' => $eventType,
            'flight' => [
                'number' => $flight->flight_number,
                'departure' => $flight->departure,
                'arrival' => $flight->arrival,
                'pilot' => $flight->pilot_name,
                'status' => $flight->status,
            ],
            'occurred_at' => now()->toIso8601String(),
        ];

        $event = WebhookEvent::create([
            'flight_id' => $flight->id,
            'event_type' => $eventType,
            'status' => 'Awaiting configuration',
            'payload' => $payload,
        ]);

        $webhookUrl = config('discord.webhook_url');

        if (blank($webhookUrl)) {
            return $event;
        }

        $discordPayload = $this->discordPayload($flight, $eventType);

        $response = Http::connectTimeout(3)->timeout(5)->post($webhookUrl, $discordPayload);

        $event->update([
            'status' => $response->successful() ? 'Delivered' : 'Failed',
            'delivered_at' => $response->successful() ? now() : null,
        ]);

        return $event;
    }

    /**
     * Build a Discord webhook embed payload for a flight event.
     *
     * @return array<string, mixed>
     */
    protected function discordPayload(Flight $flight, string $eventType): array
    {
        $color = match ($eventType) {
            'Flight took off' => 0x2ECC71, // green
            'Flight canceled' => 0xE74C3C, // red
            default => 0x3498DB, // blue
        };

        return [
            'username' => 'SKYOS Crew Center',
            'embeds' => [
                [
                    'title' => sprintf('%s · %s', $eventType, $flight->flight_number),
                    'color' => $color,
                    'fields' => [
                        [
                            'name' => 'Route',
                            'value' => sprintf('%s → %s', $flight->departure, $flight->arrival),
                            'inline' => true,
                        ],
                        [
                            'name' => 'Aircraft',
                            'value' => $flight->aircraft,
                            'inline' => true,
                        ],
                        [
                            'name' => 'Pilot',
                            'value' => $flight->pilot_name ?? 'Unassigned',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Status',
                            'value' => $flight->status,
                            'inline' => true,
                        ],
                    ],
                    'timestamp' => now()->toIso8601String(),
                    'footer' => [
                        'text' => 'SKYOS Crew Center · Flight Operations',
                    ],
                ],
            ],
        ];
    }
}
