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
                <strong>Airport Expenses</strong>
                <div class="small text-body-secondary">Define airport-specific expense costs</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.expenses.create') }}">
                <span class="nav-icon">✚</span> Add Expense
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th class="text-center">Cost</th>
                        <th class="text-center">Airports</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td><strong>{{ $expense->name }}</strong></td>
                            <td>{{ $expense->category }}</td>
                            <td>{{ $expense->description ?? '—' }}</td>
                            <td class="text-center">${{ number_format($expense->cost_cents / 100, 2) }}</td>
                            <td class="text-center">{{ $expense->airports->count() }}</td>
                            <td class="text-center">
                                <span class="badge {{ $expense->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $expense->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.expenses.edit', $expense) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?')">
                                    @csrf
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center py-4 text-body-secondary" colspan="7">No expenses have been defined yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-body-secondary">
            {{ $expenses->total() }} expense{{ $expenses->total() !== 1 ? 's' : '' }} on record &middot; {{ $expenses->perPage() }} per page
        </div>
    </div>
@endsection
