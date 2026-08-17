@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Stock Transfers') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Internal Stock Transfer</h5>
            @can('inv_transfer.add')
                <a href="{{ route('inventory.transfers.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Request Transfer</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search transfer no" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="from_store_id" class="form-control inv-select2">
                        <option value="">All From Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(request('from_store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="to_store_id" class="form-control inv-select2">
                        <option value="">All To Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(request('to_store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control inv-select2">
                        <option value="">All Status</option>
                        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'in_transit' => 'In Transit', 'received' => 'Received', 'rejected' => 'Rejected'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
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
                    <a href="{{ route('inventory.transfers.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Transfer No</th><th>From</th><th>To</th><th>Date</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $transfer)
                            <tr>
                                <td>{{ $loop->iteration + $transfers->firstItem() - 1 }}</td>
                                <td>{{ $transfer->transfer_no }}</td>
                                <td>{{ $transfer->fromStore?->name }}</td>
                                <td>{{ $transfer->toStore?->name }}</td>
                                <td>{{ $transfer->transfer_date?->format('d M Y') }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'approved' => 'info', 'in_transit' => 'warning', 'received' => 'success', 'rejected' => 'danger'][$transfer->status] ?? 'secondary' }}">
                                        {{ ucwords(str_replace('_', ' ', $transfer->status)) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('inventory.transfers.show', $transfer) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a>
                                    @if($transfer->status === 'pending')
                                        @can('inv_transfer.approve')
                                            <form method="POST" action="{{ route('inventory.transfers.approve', $transfer) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Approve and dispatch this transfer?')">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('inventory.transfers.reject', $transfer) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this transfer?')">Reject</button>
                                            </form>
                                        @endcan
                                        @can('inv_transfer.edit')
                                            <a href="{{ route('inventory.transfers.edit', $transfer) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                        @endcan
                                    @elseif($transfer->status === 'in_transit')
                                        @can('inv_transfer.receive')
                                            <a href="{{ route('inventory.transfers.receive-form', $transfer) }}" class="btn btn-sm btn-outline-primary">Receive</a>
                                        @endcan
                                    @endif
                                    @can('inv_transfer.delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteTransferModal" data-action="{{ route('inventory.transfers.destroy', $transfer) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No transfers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $transfers->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteTransferModal', 'label' => 'transfer'])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
