<?php

namespace Tests\Feature;

use App\Models\Flight;
use App\Models\Pirep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('SKYOS Administration');
    }

    public function test_non_admin_is_denied_admin_access(): void
    {
        $pilot = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);

        $this->actingAs($pilot)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($pilot)->get(route('admin.flights.index'))->assertForbidden();
        $this->actingAs($pilot)->get(route('admin.pireps.index'))->assertForbidden();
        $this->actingAs($pilot)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_from_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_flight_route(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.flights.store'), [
            'flight_number' => 'SKY501',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'route' => 'KJFK → KBOS',
            'aircraft' => 'Airbus A320neo',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i'),
            'estimated_minutes' => 90,
            'payout_cents' => 18500,
            'status' => 'Open',
        ]);

        $response->assertRedirect(route('admin.flights.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('flights', [
            'flight_number' => 'SKY501',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'status' => 'Open',
        ]);
    }

    public function test_admin_can_edit_flight_route(): void
    {
        $admin = User::factory()->admin()->create();
        $flight = Flight::factory()->create(['flight_number' => 'SKY101', 'status' => 'Open']);

        $response = $this->actingAs($admin)->put(route('admin.flights.update', $flight), [
            'flight_number' => 'SKY101',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'aircraft' => 'Boeing 737-800',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i'),
            'estimated_minutes' => 90,
            'payout_cents' => 18500,
            'status' => 'Booked',
        ]);

        $response->assertRedirect(route('admin.flights.index'));

        $this->assertDatabaseHas('flights', [
            'id' => $flight->id,
            'aircraft' => 'Boeing 737-800',
            'status' => 'Booked',
        ]);
    }

    public function test_admin_can_delete_flight_route(): void
    {
        $admin = User::factory()->admin()->create();
        $flight = Flight::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.flights.destroy', $flight));

        $response->assertRedirect(route('admin.flights.index'));
        $this->assertDatabaseMissing('flights', ['id' => $flight->id]);
    }

    public function test_admin_can_approve_and_reject_pirep(): void
    {
        $admin = User::factory()->admin()->create();
        $pirep = Pirep::factory()->create([
            'pilot_name' => 'Test Pilot',
            'flight_number' => 'SKY999',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 90,
            'status' => 'Pending review',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.pireps.update', $pirep), [
            'status' => 'Approved',
            'remarks' => 'Solid landing, well flown.',
        ]);

        $response->assertRedirect(route('admin.pireps.index'));
        $this->assertDatabaseHas('pireps', [
            'id' => $pirep->id,
            'status' => 'Approved',
            'remarks' => 'Solid landing, well flown.',
        ]);

        $this->actingAs($admin)->put(route('admin.pireps.update', $pirep), [
            'status' => 'Rejected',
            'remarks' => 'Missing block time evidence.',
        ]);

        $this->assertDatabaseHas('pireps', [
            'id' => $pirep->id,
            'status' => 'Rejected',
        ]);
    }

    public function test_admin_can_delete_pirep(): void
    {
        $admin = User::factory()->admin()->create();
        $pirep = Pirep::factory()->create([
            'pilot_name' => 'Test Pilot',
            'flight_number' => 'SKY999',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 90,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.pireps.destroy', $pirep));

        $response->assertRedirect(route('admin.pireps.index'));
        $this->assertDatabaseMissing('pireps', ['id' => $pirep->id]);
    }

    public function test_pirep_status_must_be_valid(): void
    {
        $admin = User::factory()->admin()->create();
        $pirep = Pirep::factory()->create([
            'pilot_name' => 'Test Pilot',
            'flight_number' => 'SKY999',
            'departure' => 'KJFK',
            'arrival' => 'KBOS',
            'block_minutes' => 90,
        ]);

        $this->actingAs($admin)->put(route('admin.pireps.update', $pirep), [
            'status' => 'Not a real status',
        ])->assertSessionHasErrors('status');
    }
}
