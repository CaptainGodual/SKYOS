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
                        <div class="airport-search">
                            <input class="form-control @error('origin_airport_id') is-invalid @enderror" id="origin_airport_search" type="text" value="{{ old('origin_airport_id', $tour->originAirport?->identifier ?? '') }}" placeholder="Search airports..." autocomplete="off">
                            <input type="hidden" id="origin_airport_id" name="origin_airport_id" value="{{ old('origin_airport_id', $tour->originAirport?->id ?? '') }}" required>
                            <div id="origin_airport_results" class="airport-results" style="display: none;"></div>
                        </div>
                        @error('origin_airport_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="destination_airport_id">Destination airport <span class="text-body-secondary">(required)</span></label>
                        <div class="airport-search">
                            <input class="form-control @error('destination_airport_id') is-invalid @enderror" id="destination_airport_search" type="text" value="{{ old('destination_airport_id', $tour->destinationAirport?->identifier ?? '') }}" placeholder="Search airports..." autocomplete="off">
                            <input type="hidden" id="destination_airport_id" name="destination_airport_id" value="{{ old('destination_airport_id', $tour->destinationAirport?->id ?? '') }}" required>
                            <div id="destination_airport_results" class="airport-results" style="display: none;"></div>
                        </div>
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

@push('scripts')
<script>
    function initAirportSearch(searchInputId, hiddenInputId, resultsDivId, url) {
        const searchInput = document.getElementById(searchInputId);
        const hiddenInput = document.getElementById(hiddenInputId);
        const resultsDiv = document.getElementById(resultsDivId);
        let timeout;

        searchInput.addEventListener('input', function () {
            clearTimeout(timeout);
            const query = this.value.trim();

            if (query.length < 1) {
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
                hiddenInput.value = '';
                return;
            }

            timeout = setTimeout(async () => {
                try {
                    const response = await fetch(url + '?query=' + encodeURIComponent(query));
                    const data = await response.json();
                    resultsDiv.innerHTML = '';

                    if (data.results && data.results.length > 0) {
                        data.results.forEach(function (airport) {
                            const div = document.createElement('div');
                            div.className = 'airport-result';
                            div.textContent = airport.text;
                            div.dataset.id = airport.id;
                            div.onclick = function () {
                                searchInput.value = airport.text;
                                hiddenInput.value = airport.id;
                                resultsDiv.style.display = 'none';
                                resultsDiv.innerHTML = '';
                            };
                            resultsDiv.appendChild(div);
                        });
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.style.display = 'none';
                    }
                } catch (e) {
                    console.error('Airport search error:', e);
                }
            }, 250);
        });

        // Close results when clicking outside
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAirportSearch('origin_airport_search', 'origin_airport_id', 'origin_airport_results', '{{ route('admin.airports.search') }}');
        initAirportSearch('destination_airport_search', 'destination_airport_id', 'destination_airport_results', '{{ route('admin.airports.search') }}');
    });
</script>
@endpush

<style>
    .airport-search {
        position: relative;
    }
    .airport-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 300px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #d2d8e3;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        z-index: 1050;
    }
    .airport-result {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
    }
    .airport-result:hover {
        background-color: #f8fafc;
    }
    .airport-result:last-child {
        border-bottom: none;
    }
</style>
