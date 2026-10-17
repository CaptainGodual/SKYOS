@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button class="btn-close" data-coreui-dismiss="alert" type="button"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="card page-hero mb-4 text-white">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <p class="text-white-50 text-uppercase fw-semibold small mb-2">Crew services</p>
                    <h1 class="h2 mb-2">Your inbox</h1>
                    <p class="mb-0 text-white-50">Messages from other SKYOS crew members. Opening this page marks them read.</p>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0 text-lg-end">
                    <span class="badge bg-white text-primary fs-6 px-3 py-2">✉ {{ $messages->total() }} message{{ $messages->total() !== 1 ? 's' : '' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header"><strong>Messages</strong></div>
                <div class="list-group list-group-flush">
                    @forelse ($messages as $message)
                        <div class="list-group-item py-3">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <strong class="d-block">{{ $message->subject }}</strong>
                                    <div class="small text-body-secondary">
                                        From {{ $message->senderDisplayName() }}
                                        <span class="mx-1">·</span>
                                        {{ $message->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                @if (! $message->isRead())
                                    <span class="badge bg-info text-dark">New</span>
                                @endif
                            </div>
                            <p class="mb-0 mt-3">{!! nl2br(e($message->body)) !!}</p>
                        </div>
                    @empty
                        <div class="list-group-item py-4 text-center text-body-secondary">Your inbox is empty.</div>
                    @endforelse
                </div>
                <div class="card-footer small text-body-secondary">
                    {{ $messages->total() }} message{{ $messages->total() !== 1 ? 's' : '' }} on record
                    <div class="mt-2">{{ $messages->links() }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header"><strong>Send a message</strong></div>
                <div class="card-body">
                    @include('crew-center.messages._compose')
                </div>
            </div>
        </div>
    </div>
@endsection
