<?php

namespace Tests\Feature;

use App\Models\Flight;
use App\Models\Pirep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ResetFlightRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_flight_routes_command_resets_completed_flights(): void
    {
        $user = User::factory()->create();

        Flight::factory()->create([
            'status' => 'Completed',
            'pilot_id' => $user->id,
            'pilot_name' => 'Test Pilot',
            'acars_status' => 'Completed',
            'arrived_at' => now(),
        ]);

        Flight::factory()->create([
            'status' => 'Canceled',
            'pilot_id' => $user->id,
            'pilot_name' => 'Test Pilot',
            'acars_status' => 'Canceled',
        ]);

        Flight::factory()->create([
            'status' => 'Open',
            'pilot_id' => null,
            'pilot_name' => null,
        ]);

        $exitCode = Artisan::call('app:reset-flight-routes');

        $this->assertSame(0, $exitCode);

        // Completed flight should be reset to Open
        $this->assertDatabaseHas('flights', [
            'status' => 'Open',
            'pilot_id' => null,
            'pilot_name' => null,
            'acars_status' => 'Scheduled',
        ]);

        // Canceled flight should be reset to Open
        $this->assertDatabaseHas('flights', [
            'status' => 'Open',
            'pilot_id' => null,
            'pilot_name' => null,
            'acars_status' => 'Scheduled',
        ]);

        // Already Open flight should remain Open
        $this->assertDatabaseCount('flights', 3);
        $openCount = Flight::where('status', 'Open')->whereNull('pilot_id')->count();
        $this->assertSame(3, $openCount);
    }

    public function test_reset_flight_routes_command_shows_count(): void
    {
        Flight::factory()->count(3)->create(['status' => 'Completed']);

        $output = Artisan::call('app:reset-flight-routes');

        $this->assertSame(0, $output);
        $this->assertStringContainsString('Reset 3 flight route(s)', Artisan::output());
    }

    public function test_reset_flight_routes_leaves_flights_that_have_a_pirep_alone(): void
    {
        $user = User::factory()->create();

        $reported = Flight::factory()->create([
            'status' => 'Completed',
            'pilot_id' => $user->id,
            'pilot_name' => 'Test Pilot',
            'acars_status' => 'Completed',
            'arrived_at' => now(),
        ]);

        Pirep::factory()->create([
            'flight_id' => $reported->id,
            'pilot_id' => $user->id,
            'status' => 'Approved',
            'payout_cents' => 10000,
        ]);

        $this->assertSame(0, Artisan::call('app:reset-flight-routes'));

        // A second report for the same flight is refused everywhere, so
        // re-opening it would only produce a route nobody could file for.
        $this->assertDatabaseHas('flights', [
            'id' => $reported->id,
            'status' => 'Completed',
            'pilot_id' => $user->id,
        ]);
        $this->assertStringContainsString('Skipped 1 flight(s)', Artisan::output());
    }
}
