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
            <h4 class="mb-0">Stock Adjustment</h4>
            <div>
                @can('inv_adjustment.delete')
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#adjustmentTrashModal">
                        <i class="fa-solid fa-trash"></i> Trash ({{ $trashedAdjustments->count() }})
                    </button>
                @endcan
                @can('inv_adjustment.add')
                    <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> New Adjustment</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search adjustment no" value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="store_id" class="form-control form-control-sm inv-select2">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="type" class="form-control form-control-sm inv-select2">
                        <option value="">All Types</option>
                        <option value="damage" @selected(request('type') === 'damage')>Damage</option>
                        <option value="lost" @selected(request('type') === 'lost')>Lost</option>
                        <option value="excess" @selected(request('type') === 'excess')>Excess</option>
                        <option value="physical_count" @selected(request('type') === 'physical_count')>Physical Count</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control form-control-sm inv-select2">
                        <option value="">All Status</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-3 mb-2 d-flex align-items-end flex-wrap gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                    <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Adjustment No</th><th>Store</th><th>Type</th><th>Date</th><th>Status</th><th class="text-right">Actions</th></tr>
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
                                    <span class="badge badge-{{ ['approved' => 'success', 'rejected' => 'danger'][$adjustment->status] ?? 'secondary' }}">
                                        {{ ucfirst($adjustment->status) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    @if($adjustment->status === 'pending')
                                        @can('inv_adjustment.approve')
                                            <form method="POST" action="{{ route('inventory.adjustments.approve', $adjustment) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Approve this adjustment and update stock?')">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('inventory.adjustments.reject', $adjustment) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this adjustment?')">Reject</button>
                                            </form>
                                        @endcan
                                    @endif
                                    @can('inv_adjustment.delete')
                                        <button type="button" class="btn-custom danger" data-toggle="modal" data-target="#deleteAdjustmentModal" data-action="{{ route('inventory.adjustments.destroy', $adjustment) }}"><i class="fa-solid fa-trash"></i></button>
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
@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteAdjustmentModal', 'label' => 'adjustment'])
@include('sfl-inventory::admin.partials.trash-modal', [
    'modalId' => 'adjustmentTrashModal',
    'label' => 'Adjustment',
    'rows' => $trashedAdjustments,
    'restoreRoute' => 'inventory.adjustments.restore',
    'forceRoute' => 'inventory.adjustments.force-destroy',
    'canRestore' => auth()->user()->can('inv_adjustment.delete'),
    'canForce' => auth()->user()->can('inv_adjustment.force_delete'),
])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
