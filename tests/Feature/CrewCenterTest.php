<?php

namespace Tests\Feature;

use App\Models\FinancialTransaction;
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
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
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
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flight = Flight::factory()->create([
            'flight_number' => 'SKY240',
            'pilot_id' => $user->id,
            'payout_cents' => 24800,
        ]);

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
        config()->set('discord.webhook_url', 'https://discord.com/api/webhooks/123/token');
        Http::preventStrayRequests();
        Http::fake(['https://discord.com/api/webhooks/123/token' => Http::response([], 200)]);
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flight = Flight::factory()->create(['flight_number' => 'SKY310', 'pilot_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('flights.events.store', $flight), ['event_type' => 'Flight took off']);

        $response->assertRedirect();
        $this->assertDatabaseHas('webhook_events', ['flight_id' => $flight->id, 'event_type' => 'Flight took off', 'status' => 'Delivered']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://discord.com/api/webhooks/123/token');
    }

    public function test_pirep_records_the_pilot_payout_as_an_expense(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flight = Flight::factory()->create([
            'flight_number' => 'SKY250',
            'pilot_id' => $user->id,
            'payout_cents' => 12500,
        ]);

        $this->actingAs($user)->post(route('pireps.store'), [
            'flight_number' => 'SKY250',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 60,
        ])->assertRedirect();

        $this->assertDatabaseHas(FinancialTransaction::class, [
            'type' => 'expense',
            'category' => 'Pilot Payout',
            'amount_cents' => -12500,
            'reference_type' => 'pirep',
            'reference_id' => $flight->id,
        ]);
    }

    public function test_a_flight_cannot_be_pireped_twice(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        Flight::factory()->create([
            'flight_number' => 'SKY260',
            'pilot_id' => $user->id,
            'payout_cents' => 10000,
        ]);

        $payload = [
            'flight_number' => 'SKY260',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 60,
        ];

        $this->actingAs($user)->post(route('pireps.store'), $payload)->assertSessionHas('success');
        $this->actingAs($user)->post(route('pireps.store'), $payload)->assertSessionHas('error');

        // The payout must only be settled once.
        $this->assertDatabaseCount(Pirep::class, 1);
        $this->assertDatabaseCount(FinancialTransaction::class, 1);
    }

    public function test_pilot_cannot_file_a_pirep_for_another_pilots_flight(): void
    {
        $owner = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $attacker = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flight = Flight::factory()->create([
            'flight_number' => 'SKY270',
            'pilot_id' => $owner->id,
            'payout_cents' => 20000,
        ]);

        $this->actingAs($attacker)->post(route('pireps.store'), [
            'flight_number' => 'SKY270',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 60,
        ])->assertSessionHas('error');

        $this->assertDatabaseCount(Pirep::class, 0);
        $this->assertDatabaseCount(FinancialTransaction::class, 0);
        $this->assertNotSame('Completed', $flight->fresh()->status);
    }

    public function test_pilot_cannot_dispatch_an_event_for_another_pilots_flight(): void
    {
        $owner = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $attacker = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flight = Flight::factory()->create(['pilot_id' => $owner->id]);

        $this->actingAs($attacker)
            ->post(route('flights.events.store', $flight), ['event_type' => 'Flight canceled'])
            ->assertSessionHas('error');

        $this->assertNotSame('Canceled', $flight->fresh()->status);
    }

    public function test_a_flight_cannot_be_completed_twice(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flight = Flight::factory()->create([
            'pilot_id' => $user->id,
            'status' => 'Completed',
        ]);

        $this->actingAs($user)
            ->post(route('flights.events.store', $flight), ['event_type' => 'Flight completed'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('webhook_events', 0);
    }
}
