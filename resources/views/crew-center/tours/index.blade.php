@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button class="btn-close" data-coreui-dismiss="alert" type="button"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button class="btn-close" data-coreui-dismiss="alert" type="button"></button>
        </div>
    @endif

    <div class="card page-hero mb-4 text-white">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <p class="text-white-50 text-uppercase fw-semibold small mb-2">Tour center</p>
                    <h1 class="h2 mb-2">Available tours</h1>
                    <p class="mb-0 text-white-50">Book multi-leg flight tours and earn career payouts.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @forelse($tours as $tour)
            <div class="col-xl-4 col-lg-6">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="card-title mb-1">{{ $tour->name }}</h5>
                                <p class="card-text small text-body-secondary">{{ $tour->description ?? 'No description available.' }}</p>
                            </div>
                            <span class="badge bg-success">Active</span>
                        </div>

                        <div class="mb-3">
                            <strong class="d-block text-body-secondary small text-uppercase">Route</strong>
                            <div class="fw-semibold">
                                {{ $tour->originAirport?->identifier ?? '—' }}
                                <span class="mx-1 text-body-secondary">→</span>
                                {{ $tour->destinationAirport?->identifier ?? '—' }}
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="text-body-secondary small text-uppercase">Legs</div>
                                <div>{{ $tour->legs_count ?? 0 }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-body-secondary small text-uppercase">Distance</div>
                                <div>{{ $tour->total_distance_nm ?? '—' }} NM</div>
                            </div>
                            <div class="col-6">
                                <div class="text-body-secondary small text-uppercase">Duration</div>
                                <div>{{ $tour->estimated_duration_minutes ?? '—' }} min</div>
                            </div>
                            <div class="col-6">
                                <div class="text-body-secondary small text-uppercase">Price</div>
                                <div>${{ number_format($tour->price_cents / 100, 2) }}</div>
                            </div>
                        </div>

                        @if ($bookedTourIds->contains($tour->id))
                            <button class="btn btn-outline-success w-100" disabled>Already booked</button>
                        @else
                            <form method="POST" action="{{ route('tours.book', $tour) }}">
                                @csrf
                                <button class="btn btn-primary w-100" type="submit">Book tour</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <p class="text-body-secondary">No tours are currently available.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    {{ $tours->links() }}
@endsection
