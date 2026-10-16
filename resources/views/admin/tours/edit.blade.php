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
                <strong>Edit Tour: {{ $tour->name }}</strong>
                <div class="small text-body-secondary">Manage tour legs and details</div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.tours.index') }}">
                    <span class="nav-icon">←</span> Back to tours
                </a>
                @if ($tour->is_active)
                    <form method="POST" action="{{ route('tours.update', $tour) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="is_active" value="0">
                        <button class="btn btn-sm btn-outline-warning" type="submit" onclick="return confirm('Deactivate this tour? Pilots will no longer see it.')">Deactivate</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('tours.update', $tour) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="is_active" value="1">
                        <button class="btn btn-sm btn-outline-success" type="submit">Activate</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('tours.update', $tour) }}">
                @csrf
                <input type="hidden" name="_method" value="PUT">

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Tour name <span class="text-body-secondary">(required)</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name', $tour->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="price_cents">Price (cents) <span class="text-body-secondary">(required)</span></label>
                        <input class="form-control @error('price_cents') is-invalid @enderror" id="price_cents" name="price_cents" type="number" value="{{ old('price_cents', $tour->price_cents) }}" required min="0">
                        @error('price_cents')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="origin_airport_id">Origin airport <span class="text-body-secondary">(required)</span></label>
                        <select class="form-select @error('origin_airport_id') is-invalid @enderror" id="origin_airport_id" name="origin_airport_id" required>
                            <option value="">Select origin airport</option>
                            @foreach ($airports as $airport)
                                <option value="{{ $airport->id }}" {{ old('origin_airport_id', $tour->origin_airport_id) == $airport->id ? 'selected' : '' }}>
                                    {{ $airport->identifier }} — {{ $airport->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('origin_airport_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="destination_airport_id">Destination airport <span class="text-body-secondary">(required)</span></label>
                        <select class="form-select @error('destination_airport_id') is-invalid @enderror" id="destination_airport_id" name="destination_airport_id" required>
                            <option value="">Select destination airport</option>
                            @foreach ($airports as $airport)
                                <option value="{{ $airport->id }}" {{ old('destination_airport_id', $tour->destination_airport_id) == $airport->id ? 'selected' : '' }}>
                                    {{ $airport->identifier }} — {{ $airport->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('destination_airport_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="total_distance_nm">Total distance (nautical miles)</label>
                        <input class="form-control @error('total_distance_nm') is-invalid @enderror" id="total_distance_nm" name="total_distance_nm" type="number" value="{{ old('total_distance_nm', $tour->total_distance_nm) }}" min="0">
                        @error('total_distance_nm')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="estimated_duration_minutes">Estimated duration (minutes)</label>
                        <input class="form-control @error('estimated_duration_minutes') is-invalid @enderror" id="estimated_duration_minutes" name="estimated_duration_minutes" type="number" value="{{ old('estimated_duration_minutes', $tour->estimated_duration_minutes) }}" min="0">
                        @error('estimated_duration_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $tour->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" {{ old('is_active', $tour->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active tour (visible to pilots)</label>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">Save tour details</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.tours.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Tour Legs</strong>
                <div class="small text-body-secondary">{{ $tour->legs->count() }} leg{{ $tour->legs->count() !== 1 ? 's' : '' }} in itinerary</div>
            </div>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addLegModal">
                <span class="nav-icon">✚</span> Add leg
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">#</th>
                        <th>From</th>
                        <th>To</th>
                        <th class="text-center">Distance (NM)</th>
                        <th class="text-center">Est. Time</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tour->legs as $leg)
                        <tr>
                            <td class="text-center">{{ $leg->leg_number }}</td>
                            <td>{{ $leg->departureAirport?->identifier ?? '—' }}</td>
                            <td>{{ $leg->arrivalAirport?->identifier ?? '—' }}</td>
                            <td class="text-center">{{ $leg->distance_nm ?? '—' }}</td>
                            <td class="text-center">{{ $leg->estimated_minutes ?? '—' }} min</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('tours.legs.destroy', [$tour, $leg]) }}" onsubmit="return confirm('Remove this leg?')">
                                    @csrf
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center py-4 text-body-secondary" colspan="6">No legs added yet. Add legs to build the tour itinerary.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Leg Modal -->
    <div class="modal fade" id="addLegModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Tour Leg</h5>
                    <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('tours.legs.store', $tour) }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="leg_number" value="{{ ($tour->legs->max('leg_number') ?? 0) + 1 }}">
                        <div class="mb-3">
                            <label class="form-label">Departure airport <span class="text-body-secondary">(required)</span></label>
                            <select class="form-select" name="departure_airport_id" required>
                                <option value="">Select departure airport</option>
                                @foreach ($airports as $airport)
                                    <option value="{{ $airport->id }}">{{ $airport->identifier }} — {{ $airport->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Arrival airport <span class="text-body-secondary">(required)</span></label>
                            <select class="form-select" name="arrival_airport_id" required>
                                <option value="">Select arrival airport</option>
                                @foreach ($airports as $airport)
                                    <option value="{{ $airport->id }}">{{ $airport->identifier }} — {{ $airport->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Distance (NM)</label>
                                <input class="form-control" name="distance_nm" type="number" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Est. time (minutes)</label>
                                <input class="form-control" name="estimated_minutes" type="number" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit">Add leg</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
