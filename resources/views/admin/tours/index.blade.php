@extends('admin.layout')

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

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Tours</strong>
                <div class="small text-body-secondary">Multi-leg flight tours for pilots</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.tours.create') }}">
                <span class="nav-icon">✚</span> Add tour
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tour</th>
                        <th>Route</th>
                        <th class="text-center">Legs</th>
                        <th class="text-center">Bookings</th>
                        <th class="text-center">Price</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tours as $tour)
                        <tr>
                            <td><strong>{{ $tour->name }}</strong></td>
                            <td>
                                {{ $tour->originAirport?->identifier ?? '—' }}
                                <span class="mx-1 text-body-secondary">→</span>
                                {{ $tour->destinationAirport?->identifier ?? '—' }}
                            </td>
                            <td class="text-center">{{ $tour->legs_count ?? 0 }}</td>
                            <td class="text-center">{{ $tour->bookings_count ?? 0 }}</td>
                            <td class="text-center">${{ number_format($tour->price_cents / 100, 2) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $tour->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $tour->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.tours.edit', $tour) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.tours.destroy', $tour) }}" onsubmit="return confirm('Delete this tour? All legs and bookings will be removed.')">
                                    @csrf
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center py-4 text-body-secondary" colspan="7">No tours have been created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-body-secondary">
            {{ $tours->total() }} tour{{ $tours->total() !== 1 ? 's' : '' }} on record · {{ $tours->perPage() }} per page
        </div>
    </div>
@endsection
