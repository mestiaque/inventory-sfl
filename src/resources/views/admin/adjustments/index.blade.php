@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Stock Adjustments') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Stock Adjustment</h5>
            @can('inv_adjustment.add')
                <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> New Adjustment</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search adjustment no" value="{{ request('search') }}">
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
                    <select name="type" class="form-control inv-select2">
                        <option value="">All Types</option>
                        <option value="damage" @selected(request('type') === 'damage')>Damage</option>
                        <option value="lost" @selected(request('type') === 'lost')>Lost</option>
                        <option value="excess" @selected(request('type') === 'excess')>Excess</option>
                        <option value="physical_count" @selected(request('type') === 'physical_count')>Physical Count</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control inv-select2">
                        <option value="">All Status</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Adjustment No</th><th>Store</th><th>Type</th><th>Date</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($adjustments as $adjustment)
                            <tr>
                                <td>{{ $loop->iteration + $adjustments->firstItem() - 1 }}</td>
                                <td>{{ $adjustment->adjustment_no }}</td>
                                <td>{{ $adjustment->store?->name }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $adjustment->type)) }}</td>
                                <td>{{ $adjustment->adjustment_date?->format('d M Y') }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ $adjustment->status === 'approved' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($adjustment->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @can('inv_adjustment.approve')
                                        @if($adjustment->status === 'pending')
                                            <form method="POST" action="{{ route('inventory.adjustments.approve', $adjustment) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Approve this adjustment and update stock?')">Approve</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No adjustments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $adjustments->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@include('sfl-inventory::admin.partials.select2-init')
@endsection
