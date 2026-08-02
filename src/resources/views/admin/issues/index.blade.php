@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Store Issues') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Store Issues</h5>
            @can('inv_issue.add')
                <a href="{{ route('inventory.issues.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Direct Issue</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search issue no" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="department_id" class="form-control inv-select2">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="buyer_id" class="form-control inv-select2">
                        <option value="">All Buyers</option>
                        @foreach($buyers as $buyer)
                            <option value="{{ $buyer->id }}" @selected(request('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="store_id" class="form-control inv-select2">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control inv-select2">
                        <option value="">All Status</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="authorized" @selected(request('status') === 'authorized')>Authorized</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-2 mt-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-2 mt-2">
                    <a href="{{ route('inventory.issues.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Issue No</th><th>Department</th><th>From Store</th><th>To Store</th><th>Buyer / Style</th><th>Date</th><th>Status</th><th>Dept. Receive</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($issues as $issue)
                            <tr>
                                <td>{{ $loop->iteration + $issues->firstItem() - 1 }}</td>
                                <td>{{ $issue->issue_no }}</td>
                                <td>{{ $issue->department?->name }}</td>
                                <td>{{ $issue->store?->name }}</td>
                                <td>{{ $issue->toStore?->name ?? '—' }}</td>
                                <td>
                                    {{ $issue->buyer?->name ?? '—' }}
                                    @if($issue->style)<br><small class="text-muted">{{ $issue->style }}</small>@endif
                                </td>
                                <td>{{ $issue->issue_date?->format('d M Y') }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'authorized' => 'info', 'approved' => 'success'][$issue->status] ?? 'secondary' }}">
                                        {{ ucfirst($issue->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'partial' => 'warning', 'full' => 'success'][$issue->department_receive_status] ?? 'secondary' }}">
                                        {{ ucfirst($issue->department_receive_status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @can('inv_issue.authorize')
                                        @if($issue->status === 'pending')
                                            <form action="{{ route('inventory.issues.authorize', $issue) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Authorize</button>
                                            </form>
                                        @endif
                                    @endcan
                                    @can('inv_issue.approve')
                                        @if($issue->status === 'authorized')
                                            <form action="{{ route('inventory.issues.approve', $issue) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">Approve &amp; Issue</button>
                                            </form>
                                        @endif
                                    @endcan
                                    @can('inv_issue.delete')
                                        @if(in_array($issue->status, ['pending', 'authorized']))
                                            <form action="{{ route('inventory.issues.cancel', $issue) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this challan and release the reserved quantity back to the requisition?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                            </form>
                                        @endif
                                    @endcan
                                    @can('inv_issue.print')
                                        <a href="{{ route('inventory.issues.print', $issue) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-print"></i></a>
                                    @endcan
                                    @can('inv_issue.receive')
                                        @if($issue->status === 'approved' && $issue->department_receive_status !== 'full')
                                            <a href="{{ route('inventory.issues.receive-form', $issue) }}" class="btn btn-sm btn-outline-success">Confirm Receipt</a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted">No issues found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $issues->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@include('sfl-inventory::admin.partials.select2-init')
@endsection
