@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button class="btn-close" data-coreui-dismiss="alert" type="button"></button>
        </div>
    @endif

    <div class="card page-hero mb-4 text-white">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <p class="text-white-50 text-uppercase fw-semibold small mb-2">My tours</p>
                    <h1 class="h2 mb-2">Your booked tours</h1>
                    <p class="mb-0 text-white-50">Track your tour progress and completions.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tour</th>
                                <th>Route</th>
                                <th class="text-center">Legs</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Booked</th>
                                <th class="text-center">Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings as $booking)
                                <tr>
                                    <td><strong>{{ $booking->tour->name }}</strong></td>
                                    <td>
                                        {{ $booking->tour->originAirport?->identifier ?? '—' }}
                                        <span class="mx-1 text-body-secondary">→</span>
                                        {{ $booking->tour->destinationAirport?->identifier ?? '—' }}
                                    </td>
                                    <td class="text-center">{{ $booking->tour->legs->count() }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $booking->status === 'completed' ? 'bg-success' : ($booking->status === 'booked' ? 'bg-info text-dark' : ($booking->status === 'in_progress' ? 'bg-warning text-dark' : 'bg-danger')) }}">{{ ucfirst($booking->status) }}</span>
                                    </td>
                                    <td class="text-center">{{ $booking->booked_at?->format('M d, Y') ?? '—' }}</td>
                                    <td class="text-center">{{ $booking->completed_at?->format('M d, Y') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center py-4 text-body-secondary" colspan="6">You haven't booked any tours yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-body-secondary">
                    {{ $bookings->total() }} booking{{ $bookings->total() !== 1 ? 's' : '' }} · {{ $bookings->perPage() }} per page
                </div>
            </div>
        </div>
    </div>
@endsection
