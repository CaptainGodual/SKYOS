@extends('admin.layout')

@section('content')
    @if (session('success'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button class="btn-close" data-coreui-dismiss="alert" type="button"></button></div>@endif
    @if (session('error'))<div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button class="btn-close" data-coreui-dismiss="alert" type="button"></button></div>@endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Airports</strong>
                <div class="small text-body-secondary">Imported airport reference data</div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.airports.import') }}">
                    <span class="nav-icon">⤓</span> Import CSV
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.airports.index') }}" class="row g-3 mb-3 align-items-end">
                <div class="col-md-4 col-xl-3">
                    <input class="form-control" name="search" placeholder="Search by code, name, or city" value="{{ $search ?? '' }}">
                </div>
                <div class="col-md-3 col-xl-2">
                    <input class="form-control" name="country" placeholder="Country code (e.g. US)" value="{{ $country ?? '' }}">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    @if ($search ?? $country)
                        <a class="btn btn-outline-secondary" href="{{ route('admin.airports.index') }}">Clear</a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>IATA</th>
                            <th>ICAO</th>
                            <th>Name</th>
                            <th>City</th>
                            <th>Country</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Hub</th>
                            <th class="text-center">Lat · Lng</th>
                            <th class="text-end">Time zone</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($airports as $airport)
                            <tr>
                                <td><span class="fw-semibold">{{ $airport->identifier }}</span></td>
                                <td>{{ $airport->iata_code ?? '—' }}</td>
                                <td>{{ $airport->icao_code ?? '—' }}</td>
                                <td>{{ $airport->name }}</td>
                                <td>{{ $airport->municipality ?? '—' }}</td>
                                <td>{{ $airport->country_code ?? '—' }}</td>
                                <td class="text-center">{{ $airport->type }}</td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('admin.airports.toggle-hub', $airport) }}" class="d-inline mb-0">
                                        @csrf
                                        @method('PUT')
                                        <input type="checkbox" {{ $airport->is_hub ? 'checked' : '' }}
                                            onclick="this.form.submit()" class="form-check-input mt-1">
                                    </form>
                                </td>
                                <td class="text-center">{{ $airport->latitude ?? '—' }} · {{ $airport->longitude ?? '—' }}</td>
                                <td class="text-end">{{ $airport->time_zone ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center py-4 text-body-secondary" colspan="10">No airports have been imported yet. <a href="{{ route('admin.airports.import') }}">Import a CSV</a> to get started.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $airports->links() }}
        </div>
    </div>
@endsection
