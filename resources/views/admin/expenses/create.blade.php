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
                <strong>Add Expense</strong>
                <div class="small text-body-secondary">Define a new airport-specific expense cost</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.expenses.index') }}">
                <span class="nav-icon">←</span> Back to expenses
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.expenses.store') }}">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Expense Name <span class="text-body-secondary">(required)</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name') }}" required placeholder="e.g. Ground Handling">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category">Category <span class="text-body-secondary">(required)</span></label>
                        <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                            <option value="">Select category</option>
                            <option value="Handling">Handling</option>
                            <option value="Landing">Landing</option>
                            <option value="Fuel">Fuel</option>
                            <option value="Catering">Catering</option>
                            <option value="De-icing">De-icing</option>
                            <option value="Navigation">Navigation</option>
                            <option value="Parking">Parking</option>
                            <option value="Customs">Customs</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="cost_cents">Cost (USD) <span class="text-body-secondary">(required)</span></label>
                        <input class="form-control @error('cost_cents') is-invalid @enderror" id="cost_cents" name="cost_cents" type="number" value="{{ old('cost_cents') }}" required min="0" step="0.01">
                        @error('cost_cents')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Enter amount in dollars (e.g., 50.00 for $50.00)</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="description">Description</label>
                        <input class="form-control @error('description') is-invalid @enderror" id="description" name="description" type="text" value="{{ old('description') }}" placeholder="e.g. Ground handling at major hub">
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label" for="airport_ids">Applicable Airports <span class="text-body-secondary">(optional)</span></label>
                        <div class="airport-search">
                            <input class="form-control" id="airport_search" type="text" placeholder="Search airports..." autocomplete="off">
                            <input type="hidden" id="airport_ids" name="airport_ids[]" />
                            <div id="airport_results" class="airport-results" style="display: none;"></div>
                        </div>
                        <div class="form-text">Select one or more airports where this expense applies. Leave blank for all airports.</div>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">Create Expense</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.expenses.index') }}">Cancel</a>
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
        let selectedIds = [];

        searchInput.addEventListener('input', function () {
            clearTimeout(timeout);
            const query = this.value.trim();

            if (query.length < 1) {
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
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
                                const id = airport.id;
                                if (!selectedIds.includes(id)) {
                                    selectedIds.push(id);
                                    hiddenInput.value = selectedIds.join(',');
                                    searchInput.value = '';
                                    resultsDiv.style.display = 'none';
                                    resultsDiv.innerHTML = '';
                                    renderSelected();
                                }
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

        function renderSelected() {
            // Clear and re-render selected chips if needed
        }

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAirportSearch('airport_search', 'airport_ids', 'airport_results', '{{ route('admin.airports.search') }}');
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
