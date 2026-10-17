<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\FinancialTransaction;
use App\Models\Flight;
use App\Models\FlightExpense;
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
        config()->set('services.discord.webhook_url', 'https://discord.com/api/webhooks/123/token');
        Http::preventStrayRequests();
        Http::fake(['https://discord.com/api/webhooks/123/token' => Http::response([], 200)]);
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flight = Flight::factory()->create(['flight_number' => 'SKY310', 'pilot_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('flights.events.store', $flight), ['event_type' => 'Flight took off']);

        $response->assertRedirect();
        $this->assertDatabaseHas('webhook_events', ['flight_id' => $flight->id, 'event_type' => 'Flight took off', 'status' => 'Delivered']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://discord.com/api/webhooks/123/token');
    }

    public function test_filing_a_pirep_does_not_move_any_money(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        Flight::factory()->create([
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

        // A pilot must not be able to move money without an admin approving.
        $this->assertDatabaseCount(FinancialTransaction::class, 0);
    }

    public function test_approving_a_pirep_records_the_pilot_payout_as_an_expense(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flight = Flight::factory()->create([
            'flight_number' => 'SKY254',
            'pilot_id' => $user->id,
            'payout_cents' => 12500,
        ]);

        $this->actingAs($user)->post(route('pireps.store'), [
            'flight_number' => 'SKY254',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 60,
        ])->assertRedirect();

        $pirep = Pirep::query()->sole();

        $this->actingAs($admin)
            ->put(route('admin.pireps.update', $pirep), ['status' => 'Approved'])
            ->assertRedirect(route('admin.pireps.index'));

        $this->assertDatabaseHas(FinancialTransaction::class, [
            'type' => 'expense',
            'category' => 'Pilot Payout',
            'amount_cents' => -12500,
            'reference_type' => 'pirep',
            'reference_id' => $flight->id,
        ]);
    }

    public function test_approving_a_pirep_twice_only_pays_out_once(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        Flight::factory()->create([
            'flight_number' => 'SKY255',
            'pilot_id' => $user->id,
            'payout_cents' => 8000,
        ]);

        $this->actingAs($user)->post(route('pireps.store'), [
            'flight_number' => 'SKY255',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 60,
        ])->assertRedirect();

        $pirep = Pirep::query()->sole();

        $this->actingAs($admin)->put(route('admin.pireps.update', $pirep), ['status' => 'Approved']);
        $this->actingAs($admin)->put(route('admin.pireps.update', $pirep), ['status' => 'Approved']);

        $this->assertDatabaseCount(FinancialTransaction::class, 1);
    }

    public function test_rejecting_an_approved_pirep_takes_the_payout_back_off_the_books(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        Flight::factory()->create([
            'flight_number' => 'SKY256',
            'pilot_id' => $user->id,
            'payout_cents' => 9000,
        ]);

        $this->actingAs($user)->post(route('pireps.store'), [
            'flight_number' => 'SKY256',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 60,
        ])->assertRedirect();

        $pirep = Pirep::query()->sole();

        $this->actingAs($admin)->put(route('admin.pireps.update', $pirep), ['status' => 'Approved']);
        $this->assertDatabaseCount(FinancialTransaction::class, 1);

        $this->actingAs($admin)->put(route('admin.pireps.update', $pirep), ['status' => 'Rejected']);
        $this->assertDatabaseCount(FinancialTransaction::class, 0);

        // Re-approving puts it back.
        $this->actingAs($admin)->put(route('admin.pireps.update', $pirep), ['status' => 'Approved']);
        $this->assertDatabaseCount(FinancialTransaction::class, 1);
    }

    public function test_deleting_a_pirep_reverses_its_settlement(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        Flight::factory()->create([
            'flight_number' => 'SKY257',
            'pilot_id' => $user->id,
            'payout_cents' => 9000,
        ]);

        $this->actingAs($user)->post(route('pireps.store'), [
            'flight_number' => 'SKY257',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 60,
        ])->assertRedirect();

        $pirep = Pirep::query()->sole();

        $this->actingAs($admin)->put(route('admin.pireps.update', $pirep), ['status' => 'Approved']);
        $this->assertDatabaseCount(FinancialTransaction::class, 1);

        $this->actingAs($admin)->delete(route('admin.pireps.destroy', $pirep))->assertRedirect();

        $this->assertDatabaseCount(Pirep::class, 0);
        $this->assertDatabaseCount(FinancialTransaction::class, 0);
    }

    public function test_a_flight_is_charged_the_expenses_defined_for_its_airports(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flight = Flight::factory()->create([
            'flight_number' => 'SKY251',
            'pilot_id' => $user->id,
            'payout_cents' => 0,
        ]);

        // Applies to this flight's departure airport.
        $landingFee = FlightExpense::factory()->create([
            'name' => 'Landing Fee',
            'category' => 'Landing',
            'cost_cents' => 20000,
        ]);
        $landingFee->airports()->attach(Airport::factory()->create(['identifier' => 'KJFK']));

        // Applies to an airport this flight never visits.
        $parkingFee = FlightExpense::factory()->create([
            'name' => 'Parking Fee',
            'category' => 'Parking',
            'cost_cents' => 5000,
        ]);
        $parkingFee->airports()->attach(Airport::factory()->create(['identifier' => 'EGLL']));

        $this->actingAs($user)->post(route('pireps.store'), [
            'flight_number' => 'SKY251',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 60,
        ])->assertRedirect();

        $this->actingAs($admin)->put(
            route('admin.pireps.update', Pirep::query()->sole()),
            ['status' => 'Approved'],
        );

        $this->assertDatabaseHas(FinancialTransaction::class, [
            'type' => 'expense',
            'category' => 'Landing',
            'amount_cents' => -20000,
            'reference_type' => 'pirep',
            'reference_id' => $flight->id,
        ]);

        $this->assertDatabaseMissing(FinancialTransaction::class, [
            'category' => 'Parking',
        ]);
    }

    public function test_an_expense_with_no_airports_is_charged_to_every_flight(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flight = Flight::factory()->create([
            'flight_number' => 'SKY252',
            'pilot_id' => $user->id,
            'payout_cents' => 0,
        ]);

        // The admin form promises "leave blank for all airports".
        FlightExpense::factory()->create([
            'name' => 'Fuel Surcharge',
            'category' => 'Fuel',
            'cost_cents' => 7000,
        ]);

        $this->actingAs($user)->post(route('pireps.store'), [
            'flight_number' => 'SKY252',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 60,
        ])->assertRedirect();

        $this->actingAs($admin)->put(
            route('admin.pireps.update', Pirep::query()->sole()),
            ['status' => 'Approved'],
        );

        $this->assertDatabaseHas(FinancialTransaction::class, [
            'type' => 'expense',
            'category' => 'Fuel',
            'amount_cents' => -7000,
            'reference_type' => 'pirep',
            'reference_id' => $flight->id,
        ]);
    }

    public function test_inactive_expenses_are_never_charged(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        Flight::factory()->create([
            'flight_number' => 'SKY253',
            'pilot_id' => $user->id,
            'payout_cents' => 0,
        ]);

        $inactive = FlightExpense::factory()->create([
            'name' => 'Withdrawn Fee',
            'category' => 'Parking',
            'cost_cents' => 5000,
            'is_active' => false,
        ]);
        $inactive->airports()->attach(Airport::factory()->create(['identifier' => 'KJFK']));

        $this->actingAs($user)->post(route('pireps.store'), [
            'flight_number' => 'SKY253',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 60,
        ])->assertRedirect();

        $this->actingAs($admin)->put(
            route('admin.pireps.update', Pirep::query()->sole()),
            ['status' => 'Approved'],
        );

        $this->assertDatabaseCount(FinancialTransaction::class, 0);
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

        $this->assertDatabaseCount(Pirep::class, 1);
        $this->assertDatabaseCount(FinancialTransaction::class, 0);
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

    public function test_a_pilot_can_only_hold_a_limited_number_of_flights(): void
    {
        config()->set('skyos.max_booked_flights', 2);
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flights = Flight::factory()->count(3)->create();

        $this->actingAs($user)->post(route('flights.book', $flights[0]))->assertSessionHas('success');
        $this->actingAs($user)->post(route('flights.book', $flights[1]))->assertSessionHas('success');
        $this->actingAs($user)->post(route('flights.book', $flights[2]))->assertSessionHas('error');

        $this->assertSame('Open', $flights[2]->fresh()->status);
        $this->assertNull($flights[2]->fresh()->pilot_id);
    }

    public function test_finishing_a_flight_frees_up_a_booking_slot(): void
    {
        config()->set('skyos.max_booked_flights', 1);
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flights = Flight::factory()->count(2)->create();

        $this->actingAs($user)->post(route('flights.book', $flights[0]))->assertSessionHas('success');
        $this->actingAs($user)->post(route('flights.book', $flights[1]))->assertSessionHas('error');

        $flights[0]->update(['status' => 'Completed']);

        $this->actingAs($user)->post(route('flights.book', $flights[1]))->assertSessionHas('success');
    }

    public function test_the_booking_limit_can_be_disabled(): void
    {
        config()->set('skyos.max_booked_flights', 0);
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $flights = Flight::factory()->count(4)->create();

        foreach ($flights as $flight) {
            $this->actingAs($user)->post(route('flights.book', $flight))->assertSessionHas('success');
        }

        $this->assertSame(4, Flight::query()->where('pilot_id', $user->id)->where('status', 'Booked')->count());
    }
}
