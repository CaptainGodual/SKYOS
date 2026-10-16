@extends('admin.layout')

@section('content')
    <div class="card"><div class="card-header"><strong>Add flight route</strong><div class="small text-body-secondary">Publish a new scheduled route to the crew board</div></div><div class="card-body">@include('admin.flights._form', ['action' => route('admin.flights.store'), 'buttonLabel' => 'Add route', 'flight' => null])</div></div>
@endsection
