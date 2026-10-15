<?php

namespace Tests\Feature;

use App\Models\Flight;
use App\Models\Pirep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CrewCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_open_flights(): void
    {
        $user = User::factory()->create();
        Flight::factory()->create(['flight_number' => 'SKY101']);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('SKYOS Crew Center');
        $response->assertSee('SKY101');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_valid_manual_pirep_is_saved(): void
    {
        $user = User::factory()->create();
        $flight = Flight::factory()->create(['flight_number' => 'SKY240', 'payout_cents' => 24800]);

        $response = $this->actingAs($user)->post(route('pireps.store'), [
            'flight_number' => 'SKY240',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 58,
            'landing_rate' => 145,
            'remarks' => 'Smooth arrival.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Your manual PIREP has been submitted for review.');
        $this->assertDatabaseHas(Pirep::class, [
            'flight_id' => $flight->id,
            'pilot_id' => $user->id,
            'flight_number' => 'SKY240',
            'payout_cents' => 24800,
            'status' => 'Pending review',
        ]);
    }

    public function test_takeoff_event_posts_to_configured_webhook(): void
    {
        config()->set('services.skyos.webhook_url', 'https://hooks.example.test/skyos');
        Http::preventStrayRequests();
        Http::fake(['https://hooks.example.test/skyos' => Http::response([], 200)]);
        $user = User::factory()->create();
        $flight = Flight::factory()->create(['flight_number' => 'SKY310']);

        $response = $this->actingAs($user)->post(route('flights.events.store', $flight), ['event_type' => 'Flight took off']);

        $response->assertRedirect();
        $this->assertDatabaseHas('webhook_events', ['flight_id' => $flight->id, 'event_type' => 'Flight took off', 'status' => 'Delivered']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://hooks.example.test/skyos');
    }
}
