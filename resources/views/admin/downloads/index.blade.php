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
                <strong>Downloads</strong>
                <div class="small text-body-secondary">Files available to pilots in the Crew Center</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.downloads.create') }}">
                <span class="nav-icon">✚</span> Add download
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th class="text-center">Version</th>
                        <th class="text-center">Size</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($downloads as $download)
                        <tr>
                            <td><strong>{{ $download->name }}</strong></td>
                            <td>{{ $download->category }}</td>
                            <td class="text-center">{{ $download->version ?? '—' }}</td>
                            <td class="text-center">{{ $download->size_bytes ? number_format($download->size_bytes / 1000000, 1) . ' MB' : '—' }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.downloads.edit', $download) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.downloads.destroy', $download) }}" onsubmit="return confirm('Remove this download? Pilots will no longer be able to download it.')">
                                    @csrf
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center py-4 text-body-secondary" colspan="5">No downloads have been published yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-body-secondary">
            {{ $downloads->total() }} download{{ $downloads->total() !== 1 ? 's' : '' }} on record · {{ $downloads->perPage() }} per page
            <div class="mt-2">{{ $downloads->links() }}</div>
        </div>
    </div>
@endsection
