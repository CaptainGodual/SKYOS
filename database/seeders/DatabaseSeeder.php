<?php

namespace Database\Seeders;

use App\Models\CrewMessage;
use App\Models\Download;
use App\Models\Flight;
use App\Models\WebhookEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $flights = collect([
            ['flight_number' => 'SKY101', 'departure' => 'KJFK', 'arrival' => 'EGLL', 'aircraft' => 'Boeing 787-9', 'scheduled_at' => now()->addHours(2), 'estimated_minutes' => 410, 'payout_cents' => 68500, 'status' => 'Open', 'acars_status' => 'Scheduled'],
            ['flight_number' => 'SKY284', 'departure' => 'KLAX', 'arrival' => 'KSEA', 'aircraft' => 'Airbus A320neo', 'scheduled_at' => now()->addHours(4), 'estimated_minutes' => 155, 'payout_cents' => 21800, 'status' => 'Open', 'acars_status' => 'Scheduled'],
            ['flight_number' => 'SKY412', 'departure' => 'OMDB', 'arrival' => 'WSSS', 'aircraft' => 'Boeing 777-300ER', 'scheduled_at' => now()->addHours(6), 'estimated_minutes' => 425, 'payout_cents' => 74100, 'status' => 'Open', 'acars_status' => 'Scheduled'],
            ['flight_number' => 'SKY067', 'departure' => 'EHAM', 'arrival' => 'LEMD', 'aircraft' => 'Airbus A321neo', 'scheduled_at' => now()->subMinutes(75), 'estimated_minutes' => 145, 'payout_cents' => 19600, 'status' => 'Booked', 'pilot_name' => 'Captain Jordan Lee', 'acars_status' => 'En Route', 'altitude' => 35000, 'ground_speed' => 458, 'departed_at' => now()->subMinutes(54)],
        ])->map(fn (array $flight): Flight => Flight::query()->updateOrCreate(
            ['flight_number' => $flight['flight_number']],
            $flight,
        ));

        collect([
            ['name' => 'SKYOS Operations Manual', 'category' => 'Documents', 'version' => 'v3.4', 'file_path' => '#', 'size_bytes' => 4200000, 'description' => 'The current operations handbook.'],
            ['name' => 'Fleet Livery Pack', 'category' => 'Liveries', 'version' => '2026.10', 'file_path' => '#', 'size_bytes' => 1860000000, 'description' => 'Official liveries for the SKYOS fleet.'],
            ['name' => 'ACARS Desktop Client', 'category' => 'Tools', 'version' => 'v2.8.1', 'file_path' => '#', 'size_bytes' => 63000000, 'description' => 'Flight tracking and dispatch client.'],
            ['name' => 'Pilot Quick Reference', 'category' => 'Documents', 'version' => 'v1.7', 'file_path' => '#', 'size_bytes' => 1700000, 'description' => 'At-a-glance operational procedures.'],
        ])->each(fn (array $download) => Download::query()->updateOrCreate(
            ['name' => $download['name']],
            $download,
        ));

        CrewMessage::query()->updateOrCreate(
            ['sender_name' => 'Operations', 'recipient_name' => 'Captain Alex Morgan', 'subject' => 'October schedule published'],
            ['body' => 'New routes and event flights are ready to book.'],
        );
        CrewMessage::query()->updateOrCreate(
            ['sender_name' => 'Chief Pilot', 'recipient_name' => 'Captain Alex Morgan', 'subject' => 'Welcome aboard'],
            ['body' => 'We are glad to have you flying with SKYOS.'],
        );

        WebhookEvent::query()->updateOrCreate(
            ['flight_id' => $flights->last()->id, 'event_type' => 'Flight took off'],
            ['status' => 'Delivered', 'payload' => ['flight' => 'SKY067', 'event' => 'takeoff'], 'delivered_at' => now()->subMinutes(54)],
        );
    }
}
