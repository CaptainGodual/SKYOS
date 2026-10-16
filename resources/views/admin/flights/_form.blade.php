<form method="POST" action="{{ $action }}" class="row g-3">
    @csrf
    @if ($flight && $flight->exists)
        @method('PUT')
    @endif

    <div class="col-md-6">
        <label class="form-label">Flight number</label>
        <input class="form-control @error('flight_number') is-invalid @enderror" id="flight_number" name="flight_number" value="{{ old('flight_number', $flight?->flight_number ?? '') }}" placeholder="e.g. SKY101" required>
        @error('flight_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Departure ICAO</label>
        <input class="form-control text-uppercase @error('departure') is-invalid @enderror" id="departure" name="departure" maxlength="4" value="{{ old('departure', $flight?->departure ?? '') }}" placeholder="KJFK" required>
        @error('departure')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Arrival ICAO</label>
        <input class="form-control text-uppercase @error('arrival') is-invalid @enderror" id="arrival" name="arrival" maxlength="4" value="{{ old('arrival', $flight?->arrival ?? '') }}" placeholder="KBOS" required>
        @error('arrival')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Route</label>
        <input class="form-control @error('route') is-invalid @enderror" id="route" name="route" value="{{ old('route', $flight?->route ?? $flight?->departure ? $flight->departure . ' → ' . $flight->arrival : '') }}" placeholder="e.g. JFK → LHR" required>
        @error('route')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Aircraft</label>
        <input class="form-control @error('aircraft') is-invalid @enderror" id="aircraft" name="aircraft" value="{{ old('aircraft', $flight?->aircraft ?? '') }}" placeholder="Airbus A320neo" required>
        @error('aircraft')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Scheduled departure</label>
        <input class="form-control @error('scheduled_at') is-invalid @enderror" id="scheduled_at" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at', $flight?->scheduled_at?->format('Y-m-d\H:i') ?? '') }}" required>
        @error('scheduled_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Estimated block time (minutes)</label>
        <input class="form-control @error('estimated_minutes') is-invalid @enderror" id="estimated_minutes" name="estimated_minutes" type="number" value="{{ old('estimated_minutes', $flight?->estimated_minutes ?? '') }}" min="1" required>
        @error('estimated_minutes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Payout (dollars)</label>
        <input class="form-control @error('payout_cents') is-invalid @enderror" id="payout_cents" name="payout_cents" type="number" value="{{ old('payout_cents', $flight?->payout_cents ? number_format($flight->payout_cents / 100, 2) : '') }}" step="0.01" min="0" required>
        <div class="form-text">Crew payout in dollars and cents when the PIREP is approved/paid.</div>
        @error('payout_cents')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            <option value="Open" {{ old('status', $flight?->status ?? 'Open') === 'Open' ? 'selected' : '' }}>Open</option>
            <option value="Booked" {{ old('status', $flight?->status ?? 'Open') === 'Booked' ? 'selected' : '' }}>Booked</option>
            <option value="Canceled" {{ old('status', $flight?->status ?? 'Open') === 'Canceled' ? 'selected' : '' }}>Canceled</option>
            <option value="Completed" {{ old('status', $flight?->status ?? 'Open') === 'Completed' ? 'selected' : '' }}>Completed</option>
        </select>
        @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <button class="btn btn-primary" type="submit">{{ $buttonLabel }}</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.flights.index') }}">Cancel</a>
    </div>
</form>
