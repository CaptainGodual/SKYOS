@extends('layouts.app')

@section('breadcrumb', 'Book Flights')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button class="btn-close" data-coreui-dismiss="alert" type="button"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="card page-hero mb-4 text-white">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <p class="text-white-50 text-uppercase fw-semibold small mb-2">Flight operations</p>
                    <h1 class="h2 mb-2">Open flights and the route map</h1>
                    <p class="mb-0 text-white-50">The whole SKYOS network on one map, with the flights waiting for a pilot below it.</p>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0 text-lg-end">
                    <span class="badge bg-white text-primary fs-6 px-3 py-2">✈ {{ $flights->total() }} open</span>
                </div>
            </div>
        </div>
    </div>

    @include('crew-center.flights._map')

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Open flights</strong>
                <div class="small text-body-secondary">Published routes waiting for a pilot</div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Flight</th>
                        <th>Route</th>
                        <th>Aircraft</th>
                        <th>Departure</th>
                        <th class="text-end">Block · Payout</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($flights as $flight)
                        <tr>
                            <td><span class="flight-code">{{ $flight->flight_number }}</span></td>
                            <td>
                                <strong>{{ $flight->departure }}</strong>
                                <span class="mx-1 text-body-secondary">→</span>
                                <strong>{{ $flight->arrival }}</strong>
                                @if ($flight->route)
                                    <div class="route-line">{{ $flight->route }}</div>
                                @endif
                            </td>
                            <td>{{ $flight->aircraft }}</td>
                            <td>{{ $flight->scheduled_at->format('D H:i') }}Z</td>
                            <td class="text-end">{{ $flight->estimated_minutes }} min · ${{ number_format($flight->payout_cents / 100, 2) }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('flights.book', $flight) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-primary" type="submit">Book flight</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center py-4 text-body-secondary" colspan="6">No open flights are currently scheduled.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-body-secondary">
            {{ $flights->total() }} open flight{{ $flights->total() !== 1 ? 's' : '' }} on the board
            <div class="mt-2">{{ $flights->links() }}</div>
        </div>
    </div>
@endsection
