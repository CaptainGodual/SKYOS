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

        $webhookUrl = config('services.skyos.webhook_url');

        if (blank($webhookUrl)) {
            return $event;
        }

        $response = Http::connectTimeout(3)->timeout(5)->post($webhookUrl, $payload);

        $event->update([
            'status' => $response->successful() ? 'Delivered' : 'Failed',
            'delivered_at' => $response->successful() ? now() : null,
        ]);

        return $event;
    }
}
