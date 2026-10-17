<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\CrewMessage;
use App\Models\Download;
use App\Models\Flight;
use App\Models\FlightExpense;
use App\Models\Pirep;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\TourLeg;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Renders every page a logged-in crew member can reach.
 *
 * The suite is a safety net for blade mistakes (undefined variables, broken
 * route references, missing relations) that would otherwise only show up as a
 * 500 in production.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every crew centre page, with the records each one expects to render.
     *
     * @return array<string, array{string}>
     */
    public static function crewPages(): array
    {
        return [
            'crew center' => ['crew-center.index'],
            'profile' => ['profile.edit'],
            'tours' => ['tours.index'],
            'my tours' => ['tours.my'],
            'tour detail' => ['tours.show'],
            'open flights' => ['flights.index'],
            'pilot roster' => ['pilots.index'],
            'inbox' => ['messages.index'],
        ];
    }

    /**
     * Every administration page, with the records each one expects to render.
     *
     * @return array<string, array{string}>
     */
    public static function adminPages(): array
    {
        return [
            'dashboard' => ['admin.dashboard'],
            'flights' => ['admin.flights.index'],
            'flight create' => ['admin.flights.create'],
            'flight edit' => ['admin.flights.edit'],
            'pireps' => ['admin.pireps.index'],
            'pirep detail' => ['admin.pireps.show'],
            'users' => ['admin.users.index'],
            'airports' => ['admin.airports.index'],
            'airport import' => ['admin.airports.import'],
            'downloads' => ['admin.downloads.index'],
            'download create' => ['admin.downloads.create'],
            'download edit' => ['admin.downloads.edit'],
            'tours' => ['admin.tours.index'],
            'tour create' => ['admin.tours.create'],
            'tour edit' => ['admin.tours.edit'],
            'finance' => ['admin.finance.index'],
            'expenses' => ['admin.expenses.index'],
            'expense create' => ['admin.expenses.create'],
            'expense edit' => ['admin.expenses.edit'],
        ];
    }

    /**
     * @param  array{0: string}  $page
     */
    #[DataProvider('crewPages')]
    public function test_crew_pages_render(string $routeName): void
    {
        $pilot = User::factory()->create([
            'approval_status' => User::APPROVAL_APPROVED,
            'name' => 'Piper Alpha',
        ]);

        $flight = Flight::factory()->create([
            'status' => 'Booked',
            'pilot_id' => $pilot->id,
            'pilot_name' => $pilot->name,
            'acars_status' => 'En Route',
            'departed_at' => now()->subMinutes(20),
        ]);

        Flight::factory()->create(['status' => 'Open', 'pilot_id' => null]);

        $tour = Tour::factory()->create(['is_active' => true]);
        TourLeg::factory()->count(2)->create(['tour_id' => $tour->id]);
        TourBooking::factory()->create([
            'tour_id' => $tour->id,
            'pilot_id' => $pilot->id,
            'status' => 'booked',
        ]);

        Pirep::factory()->create([
            'flight_id' => $flight->id,
            'pilot_id' => $pilot->id,
            'pilot_name' => $pilot->name,
            'status' => 'Approved',
        ]);

        Download::factory()->create();
        CrewMessage::factory()->recipient($pilot)->create(['subject' => 'Roster swap']);
        WebhookEvent::factory()->create(['flight_id' => $flight->id]);

        $response = $this->actingAs($pilot)->get(route($routeName, $routeName === 'tours.show' ? ['tour' => $tour] : []));

        $response->assertOk();
    }

    /**
     * @param  array{0: string}  $page
     */
    #[DataProvider('adminPages')]
    public function test_admin_pages_render(string $routeName): void
    {
        $admin = User::factory()->admin()->create();

        $flight = Flight::factory()->create();
        $pirep = Pirep::factory()->create(['flight_id' => $flight->id]);
        Download::factory()->create();
        $tour = Tour::factory()->create();
        TourLeg::factory()->create(['tour_id' => $tour->id]);
        $expense = FlightExpense::factory()->create();
        Airport::factory()->create(['is_hub' => true]);

        $parameters = match ($routeName) {
            'admin.flights.edit' => ['flight' => $flight],
            'admin.pireps.show' => ['pirep' => $pirep],
            'admin.downloads.edit' => ['download' => Download::query()->first()],
            'admin.tours.edit' => ['tour' => $tour],
            'admin.expenses.edit' => ['expense' => $expense],
            default => [],
        };

        $response = $this->actingAs($admin)->get(route($routeName, $parameters));

        $response->assertOk();
    }
}
