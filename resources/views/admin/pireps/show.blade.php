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
                <strong>PIREP #{{ $pirep->id }}</strong>
                <div class="small text-body-secondary">Submitted pilot report</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.pireps.index') }}">Back to review queue</a>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header"><strong>Flight details</strong></div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 35%;">Pilot</th>
                                        <td>
                                            {{ $pirep->pilot_name }}
                                            <span class="text-body-secondary small">({{ $pirep->pilot?->email }})</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Flight number</th>
                                        <td>{{ $pirep->flight_number }}</td>
                                    </tr>
                                    <tr>
                                        <th>Route</th>
                                        <td>
                                            <strong>{{ $pirep->departure }}</strong>
                                            <span class="mx-1 text-body-secondary">→</span>
                                            <strong>{{ $pirep->arrival }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Block time</th>
                                        <td>{{ $pirep->block_minutes }} minutes</td>
                                    </tr>
                                    <tr>
                                        <th>Landing rate</th>
                                        <td>{{ $pirep->landing_rate ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Payout</th>
                                        <td>${{ number_format($pirep->payout_cents / 100, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Filed at</th>
                                        <td>{{ $pirep->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                    @if ($pirep->flight)
                                        <tr>
                                            <th>Linked flight</th>
                                            <td>
                                                <a href="{{ route('admin.flights.edit', $pirep->flight) }}">
                                                    {{ $pirep->flight->flight_number }}
                                                </a>
                                                ({{ $pirep->flight->status }})
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($pirep->remarks)
                                        <tr>
                                            <th>Remarks</th>
                                            <td>{{ $pirep->remarks }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><strong>Review outcome</strong></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.pireps.update', $pirep) }}">
                                @csrf
                                <input type="hidden" name="_method" value="PUT">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" required>
                                        <option value="Pending review" {{ $pirep->status === 'Pending review' ? 'selected' : '' }}>Pending review</option>
                                        <option value="Approved" {{ $pirep->status === 'Approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="Paid" {{ $pirep->status === 'Paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="Rejected" {{ $pirep->status === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Remarks</label>
                                    <textarea class="form-control" name="remarks" rows="3">{{ old('remarks', $pirep->remarks) }}</textarea>
                                    <div class="form-text">Notes for the pilot explaining the outcome.</div>
                                </div>
                                <button class="btn btn-primary" type="submit">Update PIREP</button>
                                <a class="btn btn-outline-secondary" href="{{ route('admin.pireps.index') }}">Cancel</a>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header"><strong>Danger zone</strong></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.pireps.destroy', $pirep) }}" onsubmit="return confirm('Delete this PIREP? This cannot be undone.')">
                                @csrf
                                <input type="hidden" name="_method" value="DELETE">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete PIREP</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
