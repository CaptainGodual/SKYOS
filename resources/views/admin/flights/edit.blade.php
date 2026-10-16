@extends('admin.layout')

@section('content')
    <div class="card"><div class="card-header d-flex justify-content-between align-items-center"><div><strong>Edit flight route</strong><div class="small text-body-secondary">Update {{ $flight->flight_number }}</div></div><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.flights.index') }}">Back to routes</a></div><div class="card-body">@include('admin.flights._form', ['action' => route('admin.flights.update', $flight), 'buttonLabel' => 'Save changes', 'flight' => $flight])</div></div>
@endsection
