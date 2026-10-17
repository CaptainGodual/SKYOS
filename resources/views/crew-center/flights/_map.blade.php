{{--
    The network route map.

    Every scheduled city pair is drawn as a great circle on an equirectangular
    world map. All of the geometry is computed in PHP (see App\RouteMap) and
    rendered as plain SVG, so the map needs no JavaScript and no download from
    a third-party tile server: nothing about a visitor leaves this server, and
    the whole thing is cached with the page.

    The styling lives here rather than in resources/css/app.css on purpose:
    app.css is part of the Vite bundle, and public/build is not committed, so
    styles kept there would not appear until someone ran `npm run build` on the
    server. Keeping the widget self-contained means a plain `git pull` deploys
    it.
--}}
<style>
    .route-map {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--cui-border-color);
        border-radius: .5rem;
        background: linear-gradient(160deg, #f6f9fe, #e7f0fb);
    }

    .route-map-base { display: block; width: 100%; height: auto; }
    .route-map-overlay { position: absolute; inset: 0; width: 100%; height: 100%; }

    .route-map-route {
        fill: none;
        stroke: #9db4d4;
        stroke-width: 1.1;
        stroke-linecap: round;
        opacity: .7;
        vector-effect: non-scaling-stroke;
        transition: stroke .15s ease-in-out, stroke-width .15s ease-in-out, opacity .15s ease-in-out;
    }

    .route-map-route.is-open { stroke: #2362d9; stroke-width: 1.4; opacity: .85; }
    .route-map-route:hover { stroke: #0a2b5e; stroke-width: 2.6; opacity: 1; }

    .route-map-airport { fill: #ffffff; stroke: #4a7fc9; stroke-width: .35; }
    .route-map-airport.is-hub { fill: #2362d9; stroke: #ffffff; stroke-width: .45; }
    .route-map-airport:hover { fill: #d97706; stroke: #d97706; }

    /* "Open routes only" hides the city pairs with nothing left to book. */
    .route-map-card:has(#route-map-open-only:checked) .route-map-route:not(.is-open) {
        opacity: 0;
        pointer-events: none;
    }

    .route-map-legend { display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; }
    .route-map-legend i { display: inline-block; width: .75rem; height: .75rem; margin-right: .35rem; vertical-align: middle; }
    .route-map-legend .swatch-open { background: #2362d9; }
    .route-map-legend .swatch-idle { background: #9db4d4; }
    .route-map-legend .swatch-hub { border-radius: 50%; background: #2362d9; }
</style>

<div class="card route-map-card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <strong>Network route map</strong>
            <div class="small text-body-secondary">Every scheduled city pair, drawn as a great circle. Hover a line or a marker for the detail.</div>
        </div>
        @if ($map['routes'] !== [])
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" id="route-map-open-only" type="checkbox" role="switch">
                <label class="form-check-label small" for="route-map-open-only">Open routes only</label>
            </div>
        @endif
    </div>

    <div class="card-body">
        @if ($map['routes'] === [])
            <p class="mb-0 text-body-secondary">
                No route can be drawn yet. Import your airports with latitude and longitude, then schedule a flight between two of them.
            </p>
        @else
            <div class="route-map">
                <img class="route-map-base" src="{{ asset('images/world-outline.svg') }}" alt="" width="720" height="360" loading="lazy">
                <svg class="route-map-overlay" viewBox="0 0 360 180" role="img" aria-label="Map of every scheduled route in the SKYOS network">
                    <g>
                        @foreach ($map['routes'] as $route)
                            <path class="route-map-route{{ $route['open'] > 0 ? ' is-open' : '' }}" d="{{ $route['path'] }}"><title>{{ $route['label'] }}</title></path>
                        @endforeach
                    </g>
                    <g>
                        @foreach ($map['airports'] as $airport)
                            <circle class="route-map-airport{{ $airport['hub'] ? ' is-hub' : '' }}" cx="{{ $airport['x'] }}" cy="{{ $airport['y'] }}" r="{{ $airport['hub'] ? 1.8 : 1.2 }}"><title>{{ $airport['label'] }}</title></circle>
                        @endforeach
                    </g>
                </svg>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                <div class="route-map-legend small text-body-secondary">
                    <span><i class="swatch-open"></i>Open flight</span>
                    <span><i class="swatch-idle"></i>Fully booked</span>
                    <span><i class="swatch-hub"></i>Hub</span>
                </div>
                <div class="small text-body-secondary">
                    {{ count($map['routes']) }} route{{ count($map['routes']) !== 1 ? 's' : '' }} · {{ count($map['airports']) }} airports
                    @if ($map['unplottable'] > 0)
                        · {{ $map['unplottable'] }} route{{ $map['unplottable'] !== 1 ? 's' : '' }} missing airport coordinates
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
