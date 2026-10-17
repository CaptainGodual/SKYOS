<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('flights.index'))->assertRedirect(route('login'));
    }

    public function test_it_lists_the_open_flights_and_offers_to_book_them(): void
    {
        $pilot = $this->pilot();
        $open = Flight::factory()->create(['flight_number' => 'SKY101', 'status' => 'Open', 'pilot_id' => null]);
        Flight::factory()->create(['flight_number' => 'SKY202', 'status' => 'Booked', 'pilot_id' => $pilot->id]);

        $response = $this->actingAs($pilot)->get(route('flights.index'));

        $response->assertOk();
        $response->assertSee('SKY101');
        $response->assertDontSee('SKY202');
        $response->assertSee(route('flights.book', $open), false);
    }

    public function test_it_draws_every_route_it_can_place_on_the_map(): void
    {
        $pilot = $this->pilot();

        Airport::factory()->create(['identifier' => 'KJFK', 'latitude' => 40.6413, 'longitude' => -73.7781, 'is_hub' => true]);
        Airport::factory()->create(['identifier' => 'EGLL', 'latitude' => 51.47, 'longitude' => -0.4543, 'is_hub' => true]);

        Flight::factory()->create(['departure' => 'KJFK', 'arrival' => 'EGLL', 'status' => 'Open', 'pilot_id' => null]);

        $response = $this->actingAs($pilot)->get(route('flights.index'));

        $response->assertOk();

        // The base map, an arc for the route, and a marker at each end: the
        // canvas is 360 units by 180, so New York sits at x = 106.22, y = 49.36.
        $response->assertSee('images/world-outline.svg', false);
        $response->assertSee('aria-current="page">Book Flights', false);
        $response->assertSee('class="route-map-route is-open"', false);
        $response->assertSee('d="M 106.22 49.36', false);
        $response->assertSee('cx="106.22" cy="49.36"', false);
        $response->assertSee('cx="179.55" cy="38.53"', false);
        $response->assertSee('1 route · 2 airports');
    }

    public function test_a_route_with_nothing_left_to_book_is_drawn_as_a_quiet_line(): void
    {
        $pilot = $this->pilot();

        Airport::factory()->create(['identifier' => 'KJFK', 'latitude' => 40.6413, 'longitude' => -73.7781]);
        Airport::factory()->create(['identifier' => 'KLAX', 'latitude' => 33.9416, 'longitude' => -118.4085]);

        Flight::factory()->create(['departure' => 'KJFK', 'arrival' => 'KLAX', 'status' => 'Booked', 'pilot_id' => $pilot->id]);

        $response = $this->actingAs($pilot)->get(route('flights.index'));

        $response->assertOk();
        $response->assertSee('class="route-map-route"', false);
        $response->assertDontSee('class="route-map-route is-open"', false);
    }

    public function test_it_reports_the_routes_it_could_not_place_on_the_map(): void
    {
        $pilot = $this->pilot();

        Airport::factory()->create(['identifier' => 'KJFK', 'latitude' => 40.6413, 'longitude' => -73.7781]);
        Airport::factory()->create(['identifier' => 'EGLL', 'latitude' => 51.47, 'longitude' => -0.4543]);
        // A hub without coordinates is no use to the map, and ZZZZ is not on
        // file at all.
        Airport::factory()->create(['identifier' => 'OMDB', 'latitude' => null, 'longitude' => null, 'is_hub' => true]);

        Flight::factory()->create(['departure' => 'KJFK', 'arrival' => 'EGLL', 'status' => 'Open', 'pilot_id' => null]);
        Flight::factory()->create(['departure' => 'KJFK', 'arrival' => 'OMDB', 'status' => 'Open', 'pilot_id' => null]);
        Flight::factory()->create(['departure' => 'KJFK', 'arrival' => 'ZZZZ', 'status' => 'Open', 'pilot_id' => null]);

        $response = $this->actingAs($pilot)->get(route('flights.index'));

        $response->assertOk();
        $response->assertSee('1 route · 2 airports');
        $response->assertSee('2 routes missing airport coordinates');
    }

    public function test_it_explains_itself_when_no_route_can_be_drawn(): void
    {
        $pilot = $this->pilot();
        Flight::factory()->create(['departure' => 'KJFK', 'arrival' => 'EGLL', 'status' => 'Open', 'pilot_id' => null]);

        $this->actingAs($pilot)->get(route('flights.index'))
            ->assertOk()
            ->assertSee('No route can be drawn yet.');
    }

    /**
     * Create an approved pilot.
     */
    private function pilot(): User
    {
        return User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
    }
}
