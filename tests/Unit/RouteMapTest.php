<?php

namespace Tests\Unit;

use App\RouteMap;
use PHPUnit\Framework\TestCase;

/**
 * The geometry behind the route map.
 *
 * App\RouteMap keeps the maths in static methods that touch neither the
 * database nor the container, so the drawing can be checked directly.
 */
class RouteMapTest extends TestCase
{
    public function test_it_projects_a_coordinate_onto_the_map_canvas(): void
    {
        $this->assertSame([180.0, 90.0], RouteMap::project(0.0, 0.0));

        [$x, $y] = RouteMap::project(51.47, -0.4543);

        $this->assertEqualsWithDelta(179.5457, $x, 0.0001);
        $this->assertEqualsWithDelta(38.53, $y, 0.0001);
    }

    public function test_it_measures_the_great_circle_distance_in_nautical_miles(): void
    {
        // New York to London is 3,006 nm on the published great circle route.
        $this->assertEqualsWithDelta(3006, RouteMap::distanceNm(40.6413, -73.7781, 51.47, -0.4543), 15);

        // Los Angeles to Sydney is the long haul across the Pacific.
        $this->assertEqualsWithDelta(6506, RouteMap::distanceNm(33.9416, -118.4085, -33.9399, 151.1753), 40);

        $this->assertSame(0, RouteMap::distanceNm(51.47, -0.4543, 51.47, -0.4543));
    }

    public function test_it_draws_a_route_between_the_two_airports(): void
    {
        $path = RouteMap::greatCirclePath(40.6413, -73.7781, 51.47, -0.4543);

        // The numbers are written as short as they can be without losing
        // detail, and a straight hop is a single subpath.
        $this->assertStringStartsWith('M 106.22 49.36', $path);
        $this->assertStringEndsWith('179.55 38.53', $path);
        $this->assertSame(1, substr_count($path, 'M'));
    }

    public function test_it_bends_the_route_towards_the_pole(): void
    {
        $points = RouteMap::greatCirclePoints(40.6413, -73.7781, 51.47, -0.4543);

        $this->assertGreaterThan(2, count($points));

        // A great circle from New York to London climbs north of the straight
        // line on the map, which is the bow the classic route map shows.
        $middle = $points[intdiv(count($points), 2)];
        $straightLine = (49.36 + 38.53) / 2;

        $this->assertLessThan($straightLine, $middle[1]);
    }

    public function test_a_route_over_the_date_line_is_drawn_in_two_segments(): void
    {
        // Los Angeles to Sydney passes the antimeridian on its way south, so
        // the line has to leave one edge and come back in on the other.
        $path = RouteMap::greatCirclePath(33.9416, -118.4085, -33.9399, 151.1753);

        $this->assertSame(2, substr_count($path, 'M'));

        // Auckland to Los Angeles crosses it too, but a short domestic hop must
        // never be split.
        $this->assertSame(2, substr_count(RouteMap::greatCirclePath(-37.0082, 174.7850, 33.9416, -118.4085), 'M'));
        $this->assertSame(1, substr_count(RouteMap::greatCirclePath(43.6777, -79.6248, 40.6413, -73.7781), 'M'));
    }

    public function test_every_drawn_point_stays_on_the_canvas(): void
    {
        $routes = [
            [40.6413, -73.7781, -33.9399, 151.1753],
            [-37.0082, 174.7850, 51.47, -0.4543],
            [-33.3930, -70.7858, 35.5494, 139.7798],
            [0.0, 0.0, 0.0, 0.0],
            [90.0, 0.0, -90.0, 0.0],
        ];

        foreach ($routes as $route) {
            foreach (RouteMap::greatCirclePoints(...$route) as [$x, $y]) {
                $this->assertGreaterThanOrEqual(0.0, $x);
                $this->assertLessThanOrEqual(360.0, $x);
                $this->assertGreaterThanOrEqual(0.0, $y);
                $this->assertLessThanOrEqual(180.0, $y);
            }

            $path = RouteMap::greatCirclePath(...$route);

            $this->assertStringNotContainsStringIgnoringCase('nan', $path);
            $this->assertStringNotContainsStringIgnoringCase('inf', $path);
        }
    }
}
