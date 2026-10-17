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
                        <label class="form-label" for="airport_search">Applicable Airports <span class="text-body-secondary">(optional)</span></label>
                        @include('admin.expenses._airport_picker')
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input name="is_active" type="hidden" value="0">
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
