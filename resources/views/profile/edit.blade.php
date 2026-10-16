@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button class="btn-close" data-coreui-dismiss="alert" type="button"></button>
        </div>
    @endif

    <div class="card page-hero mb-4 text-white">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <p class="text-white-50 text-uppercase fw-semibold small mb-2">Pilot profile</p>
                    <h1 class="h2 mb-2">Your profile</h1>
                    <p class="mb-0 text-white-50">Update your name, email, and home country.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header"><strong>Profile information</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}" class="row g-3">
                        @csrf
                        @method('PUT')

                        <div class="col-12">
                            <label class="form-label" for="name">Full name</label>
                            <input class="form-control @error('name') is-invalid @enderror"
                                id="name" name="name" type="text"
                                value="{{ old('name', $user->name) }}" required autocomplete="name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="email">Email address</label>
                            <input class="form-control @error('email') is-invalid @enderror"
                                id="email" name="email" type="email"
                                value="{{ old('email', $user->email) }}" required autocomplete="email">
                            <div class="form-text">Your email is only visible to you.</div>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="country">Country</label>
                            <select class="form-select @error('country') is-invalid @enderror"
                                id="country" name="country" required>
                                <option value="">Select a country</option>
                                @foreach ($countries as $code => $country)
                                    <option value="{{ $code }}" {{ old('country', $user->country) === $code ? 'selected' : '' }}>
                                        {{ $country }}
                                    </option>
                                @endforeach
                            </select>
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="hub_id">Home Hub</label>
                            <select class="form-select @error('hub_id') is-invalid @enderror"
                                id="hub_id" name="hub_id">
                                <option value="">No home hub</option>
                                @foreach ($hubAirports as $hubAirport)
                                    <option value="{{ $hubAirport->id }}" {{ old('hub_id', $user->hub_id) == $hubAirport->id ? 'selected' : '' }}>
                                        {{ $hubAirport->identifier }} {{ $hubAirport->iata_code ? '('.$hubAirport->iata_code.')' : '' }} — {{ $hubAirport->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Choose your home base from airports marked as crew hubs.</div>
                            @error('hub_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Save changes</button>
                            <a class="btn btn-outline-secondary" href="{{ route('crew-center.index') }}">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
