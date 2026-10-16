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
                <strong>Add Download</strong>
                <div class="small text-body-secondary">Upload a file for pilots to download</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.downloads.index') }}">
                <span class="nav-icon">←</span> Back to downloads
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.downloads.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Name <span class="text-body-secondary">(required)</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name') }}" required placeholder="e.g. A320 Livery Pack">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category">Category <span class="text-body-secondary">(required)</span></label>
                        <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                            <option value="">Select category</option>
                            <option value="Documents" {{ old('category') === 'Documents' ? 'selected' : '' }}>Documents</option>
                            <option value="Liveries" {{ old('category') === 'Liveries' ? 'selected' : '' }}>Liveries</option>
                            <option value="Tools" {{ old('category') === 'Tools' ? 'selected' : '' }}>Tools</option>
                            <option value="Manuals" {{ old('category') === 'Manuals' ? 'selected' : '' }}>Manuals</option>
                            <option value="Other" {{ old('category') === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="version">Version</label>
                        <input class="form-control @error('version') is-invalid @enderror" id="version" name="version" type="text" value="{{ old('version') }}" placeholder="e.g. v1.0">
                        @error('version')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="file">File <span class="text-body-secondary">(required)</span></label>
                        <input class="form-control @error('file') is-invalid @enderror" id="file" name="file" type="file" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Maximum file size: 100 MB. Files are stored securely and only accessible to approved pilots.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">Upload download</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.downloads.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
