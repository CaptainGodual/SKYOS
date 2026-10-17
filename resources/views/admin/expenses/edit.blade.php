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
                <strong>Edit Expense: {{ $expense->name }}</strong>
                <div class="small text-body-secondary">Edit expense details</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.expenses.index') }}">
                <span class="nav-icon">←</span> Back to expenses
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.expenses.update', $expense) }}">
                @csrf
                <input type="hidden" name="_method" value="PUT">

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Expense Name <span class="text-body-secondary">(required)</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name', $expense->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category">Category <span class="text-body-secondary">(required)</span></label>
                        <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                            <option value="">Select category</option>
                            <option value="Handling" {{ old('category', $expense->category) === 'Handling' ? 'selected' : '' }}>Handling</option>
                            <option value="Landing" {{ old('category', $expense->category) === 'Landing' ? 'selected' : '' }}>Landing</option>
                            <option value="Fuel" {{ old('category', $expense->category) === 'Fuel' ? 'selected' : '' }}>Fuel</option>
                            <option value="Catering" {{ old('category', $expense->category) === 'Catering' ? 'selected' : '' }}>Catering</option>
                            <option value="De-icing" {{ old('category', $expense->category) === 'De-icing' ? 'selected' : '' }}>De-icing</option>
                            <option value="Navigation" {{ old('category', $expense->category) === 'Navigation' ? 'selected' : '' }}>Navigation</option>
                            <option value="Parking" {{ old('category', $expense->category) === 'Parking' ? 'selected' : '' }}>Parking</option>
                            <option value="Customs" {{ old('category', $expense->category) === 'Customs' ? 'selected' : '' }}>Customs</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="cost_cents">Cost (USD) <span class="text-body-secondary">(required)</span></label>
                        <input class="form-control @error('cost_cents') is-invalid @enderror" id="cost_cents" name="cost_cents" type="number" value="{{ old('cost_cents', $expense->cost_cents / 100) }}" required min="0" step="0.01">
                        @error('cost_cents')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Enter amount in dollars</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="description">Description</label>
                        <input class="form-control @error('description') is-invalid @enderror" id="description" name="description" type="text" value="{{ old('description', $expense->description) }}">
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label" for="airport_search">Applicable Airports <span class="text-body-secondary">(optional)</span></label>
                        @include('admin.expenses._airport_picker', ['selectedAirports' => $expense->airports])
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input name="is_active" type="hidden" value="0">
                    <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $expense->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">Save Changes</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.expenses.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
