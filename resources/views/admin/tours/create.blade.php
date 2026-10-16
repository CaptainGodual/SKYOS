@extends('admin.layout')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button class="btn-close" data-coreui-dismiss="alert" type="button"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Create Tour</strong>
                <div class="small text-body-secondary">Create a new multi-leg tour for pilots</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.tours.index') }}">
                <span class="nav-icon">←</span> Back to tours
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.tours.store') }}">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Tour name <span class="text-body-secondary">(required)</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name') }}" required placeholder="e.g. Transatlantic Crossing">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="price_cents">Price (cents) <span class="text-body-secondary">(required)</span></label>
                        <input class="form-control @error('price_cents') is-invalid @enderror" id="price_cents" name="price_cents" type="number" value="{{ old('price_cents', 0) }}" required min="0">
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
                                <option value="{{ $airport->id }}">{{ $airport->identifier }} — {{ $airport->name }}</option>
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
                                <option value="{{ $airport->id }}">{{ $airport->identifier }} — {{ $airport->name }}</option>
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
                        <input class="form-control @error('total_distance_nm') is-invalid @enderror" id="total_distance_nm" name="total_distance_nm" type="number" value="{{ old('total_distance_nm') }}" min="0">
                        @error('total_distance_nm')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="estimated_duration_minutes">Estimated duration (minutes)</label>
                        <input class="form-control @error('estimated_duration_minutes') is-invalid @enderror" id="estimated_duration_minutes" name="estimated_duration_minutes" type="number" value="{{ old('estimated_duration_minutes') }}" min="0">
                        @error('estimated_duration_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active tour (visible to pilots)</label>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">Create tour</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.tours.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
