@extends('layouts.app')

@section('breadcrumb', 'Pilot Roster')

@section('content')
    <div class="card page-hero mb-4 text-white">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <p class="text-white-50 text-uppercase fw-semibold small mb-2">Crew services</p>
                    <h1 class="h2 mb-2">Pilot roster</h1>
                    <p class="mb-0 text-white-50">Every pilot on the SKYOS crew and their flying record. Crew members are listed by first name and surname initial.</p>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0 text-lg-end">
                    <span class="badge bg-white text-primary fs-6 px-3 py-2">✦ {{ $pilots->total() }} pilot{{ $pilots->total() !== 1 ? 's' : '' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <strong>Crew directory</strong>
                <div class="small text-body-secondary">Hours are the block time logged across every PIREP on file.</div>
            </div>
            <form method="GET" action="{{ route('pilots.index') }}" class="d-flex gap-2">
                <input class="form-control form-control-sm" name="search" value="{{ $search }}" placeholder="Pilot name or hub" aria-label="Search the roster">
                <button class="btn btn-sm btn-outline-primary" type="submit">Search</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Pilot</th>
                        <th>Home hub</th>
                        <th class="text-end">Hours</th>
                        <th class="text-end">Flights</th>
                        <th class="text-end">Member since</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pilots as $pilot)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-primary-subtle text-primary">{{ $pilot->initial() ?: '·' }}</span>
                                    <span>
                                        <strong class="d-block">{{ $pilot->displayName() }}</strong>
                                        @if ($pilot->isAdmin())
                                            <span class="badge bg-secondary">Staff</span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if ($pilot->homeHub)
                                    <strong>{{ $pilot->homeHub->identifier }}</strong>
                                    <div class="small text-body-secondary">{{ $pilot->homeHub->name }}</div>
                                @else
                                    <span class="text-body-secondary">—</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format(((int) $pilot->pireps_sum_block_minutes) / 60, 1) }}</td>
                            <td class="text-end">{{ $pilot->pireps_count }}</td>
                            <td class="text-end">{{ $pilot->created_at->format('M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center py-4 text-body-secondary" colspan="5">No pilots match that search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-body-secondary">
            {{ $pilots->total() }} pilot{{ $pilots->total() !== 1 ? 's' : '' }} on the roster
            <div class="mt-2">{{ $pilots->links() }}</div>
        </div>
    </div>
@endsection
