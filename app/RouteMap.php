<?php

namespace App;

use App\Models\Airport;
use App\Models\Flight;
use Illuminate\Support\Collection;

/**
 * Builds the data behind the crew centre route map.
 *
 * The map is a plain SVG drawn on an equirectangular canvas: 360 units across
 * for the 360 degrees of longitude and 180 units down for the 180 degrees of
 * latitude, so the map units are degrees and the projection is the two
 * additions in project(). Routes are drawn as great circles - the shortest
 * path between two airports, and the reason the classic route map arcs bow
 * towards the poles.
 *
 * Everything is computed here rather than in the browser so the drawing can be
 * unit tested and the page needs no JavaScript of its own.
 */
class RouteMap
{
    /**
     * How far apart the points sampled along a great circle sit, in degrees.
     */
    private const SAMPLE_DEGREES = 2.5;

    /**
     * Mean earth radius, in nautical miles.
     */
    private const EARTH_RADIUS_NM = 3440.065;

    /**
     * The routes and airports that make up the map.
     *
     * @return array{
     *     routes: array<int, array<string, mixed>>,
     *     airports: array<int, array<string, mixed>>,
     *     unplottable: int
     * }
     */
    public function build(): array
    {
        // One row per scheduled city pair, with the number of flights on it
        // and how many of those are still waiting for a pilot.
        $scheduled = Flight::query()
            ->selectRaw(
                'departure, arrival, COUNT(*) as flight_count, SUM(CASE WHEN status = ? AND pilot_id IS NULL THEN 1 ELSE 0 END) as open_count',
                ['Open'],
            )
            ->groupBy('departure', 'arrival')
            ->get();

        $airports = Airport::query()
            ->whereIn('identifier', $scheduled->flatMap(fn ($route) => [$route->departure, $route->arrival])->filter()->unique()->values())
            // Hubs are drawn even when no scheduled route calls at them yet.
            ->orWhere('is_hub', true)
            ->get()
            ->keyBy('identifier');

        $routes = [];
        $plotted = [];
        $unplottable = 0;

        foreach ($scheduled as $route) {
            $departure = $airports->get($route->departure);
            $arrival = $airports->get($route->arrival);

            if (! $departure || ! $arrival || ! $this->isPlottable($departure) || ! $this->isPlottable($arrival)) {
                $unplottable++;

                continue;
            }

            $path = self::greatCirclePath(
                (float) $departure->latitude,
                (float) $departure->longitude,
                (float) $arrival->latitude,
                (float) $arrival->longitude,
            );

            if ($path === '') {
                $unplottable++;

                continue;
            }

            $distance = self::distanceNm(
                (float) $departure->latitude,
                (float) $departure->longitude,
                (float) $arrival->latitude,
                (float) $arrival->longitude,
            );

            $flights = (int) $route->flight_count;
            $open = (int) $route->open_count;

            $routes[] = [
                'from' => (string) $departure->identifier,
                'to' => (string) $arrival->identifier,
                'path' => $path,
                'distance_nm' => $distance,
                'flights' => $flights,
                'open' => $open,
                'label' => $this->routeLabel($departure, $arrival, $distance, $flights, $open),
            ];

            // Both ends are worth a marker once they are on the map.
            $plotted[$departure->identifier] = ($plotted[$departure->identifier] ?? 0) + 1;
            $plotted[$arrival->identifier] = ($plotted[$arrival->identifier] ?? 0) + 1;
        }

        // Longest first, so the shorter hops stay readable on top of them.
        usort($routes, fn (array $left, array $right) => $right['distance_nm'] <=> $left['distance_nm']);

        return [
            'routes' => $routes,
            'airports' => $this->markers($airports, $plotted),
            'unplottable' => $unplottable,
        ];
    }

    /**
     * The markers to draw, with the number of routes each one anchors.
     *
     * @param  Collection<string, Airport>  $airports
     * @param  array<string, int>  $plotted
     * @return array<int, array<string, mixed>>
     */
    private function markers(Collection $airports, array $plotted): array
    {
        $markers = [];

        foreach ($airports as $airport) {
            if (! $this->isPlottable($airport)) {
                continue;
            }

            // A hub is drawn whether or not a route was plotted to it, but an
            // ordinary airport only makes the map when a line reaches it.
            if (! isset($plotted[$airport->identifier]) && ! $airport->is_hub) {
                continue;
            }

            [$x, $y] = self::project((float) $airport->latitude, (float) $airport->longitude);

            $markers[] = [
                'identifier' => (string) $airport->identifier,
                'name' => (string) ($airport->name ?? $airport->identifier),
                'municipality' => (string) ($airport->municipality ?? ''),
                'hub' => (bool) $airport->is_hub,
                'x' => round($x, 2),
                'y' => round($y, 2),
                'routes' => $plotted[$airport->identifier] ?? 0,
                'label' => $this->airportLabel($airport),
            ];
        }

        usort($markers, fn (array $left, array $right) => $left['identifier'] <=> $right['identifier']);

        return $markers;
    }

    /**
     * Whether an airport carries the coordinates the map needs.
     */
    private function isPlottable(Airport $airport): bool
    {
        return $airport->latitude !== null && $airport->longitude !== null;
    }

    /**
     * The hover text for a route line.
     */
    private function routeLabel(Airport $departure, Airport $arrival, int $distance, int $flights, int $open): string
    {
        $label = sprintf(
            '%s → %s · %s nm · %d flight%s',
            $departure->identifier,
            $arrival->identifier,
            number_format($distance),
            $flights,
            $flights === 1 ? '' : 's',
        );

        return $open > 0 ? $label.' · '.$open.' open now' : $label.' · none open';
    }

    /**
     * The hover text for an airport marker.
     */
    private function airportLabel(Airport $airport): string
    {
        $label = $airport->identifier;

        if ($airport->name) {
            $label .= ' — '.$airport->name;
        }

        if ($airport->municipality) {
            $label .= ' ('.$airport->municipality.')';
        }

        return $airport->is_hub ? 'Hub '.$label : $label;
    }

    /**
     * Project a coordinate onto the map canvas.
     *
     * @return array{0: float, 1: float}
     */
    public static function project(float $latitude, float $longitude): array
    {
        return [$longitude + 180, 90 - $latitude];
    }

    /**
     * The SVG path of the great circle between two coordinates.
     *
     * A route that crosses the antimeridian starts a new subpath when it does,
     * so the line leaves the right edge and comes back in on the left instead
     * of streaking back across the whole map.
     */
    public static function greatCirclePath(float $fromLat, float $fromLon, float $toLat, float $toLon): string
    {
        $points = self::greatCirclePoints($fromLat, $fromLon, $toLat, $toLon);

        $path = '';
        $previousX = null;

        foreach ($points as [$x, $y]) {
            $move = $previousX === null || abs($x - $previousX) > 180;

            $path .= ($path === '' ? '' : ' ').($move ? 'M' : 'L').' '.self::number($x).' '.self::number($y);

            $previousX = $x;
        }

        return $path;
    }

    /**
     * Sample the great circle between two coordinates in map units.
     *
     * @return array<int, array{0: float, 1: float}>
     */
    public static function greatCirclePoints(float $fromLat, float $fromLon, float $toLat, float $toLon): array
    {
        $lat1 = deg2rad($fromLat);
        $lon1 = deg2rad($fromLon);
        $lat2 = deg2rad($toLat);
        $lon2 = deg2rad($toLon);

        // The central angle between the two points. Clamped, because rounding
        // can push the cosine of antipodal points just outside [-1, 1].
        $angle = acos(min(1.0, max(-1.0, sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lon2 - $lon1))));

        if (rad2deg($angle) < 0.01) {
            return [self::project($fromLat, $fromLon), self::project($toLat, $toLon)];
        }

        $steps = max(2, (int) ceil(rad2deg($angle) / self::SAMPLE_DEGREES));
        $points = [];

        for ($step = 0; $step <= $steps; $step++) {
            $fraction = $step / $steps;

            // Spherical linear interpolation: the arc a great circle follows
            // between the two points.
            $a = sin((1 - $fraction) * $angle) / sin($angle);
            $b = sin($fraction * $angle) / sin($angle);

            $x = $a * cos($lat1) * cos($lon1) + $b * cos($lat2) * cos($lon2);
            $y = $a * cos($lat1) * sin($lon1) + $b * cos($lat2) * sin($lon2);
            $z = $a * sin($lat1) + $b * sin($lat2);

            $points[] = self::project(
                rad2deg(atan2($z, sqrt($x * $x + $y * $y))),
                rad2deg(atan2($y, $x)),
            );
        }

        return $points;
    }

    /**
     * The great circle distance between two coordinates, in nautical miles.
     */
    public static function distanceNm(float $fromLat, float $fromLon, float $toLat, float $toLon): int
    {
        $lat1 = deg2rad($fromLat);
        $lat2 = deg2rad($toLat);
        $deltaLon = deg2rad($toLon - $fromLon);

        $haversine = sin(($lat2 - $lat1) / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;

        return (int) round(self::EARTH_RADIUS_NM * 2 * asin(min(1.0, sqrt($haversine))));
    }

    /**
     * A map coordinate as short as it can be written without losing detail.
     */
    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
