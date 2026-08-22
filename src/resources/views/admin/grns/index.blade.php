@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Goods Receive Note (GRN)') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Goods Receive Note (GRN)</h5>
            @can('inv_grn.add')
                <a href="{{ route('inventory.grns.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add GRN</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search GRN number" value="{{ request('search') }}">
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
                    <select name="supplier_id" class="form-control inv-select2">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="source_type" class="form-control inv-select2">
                        <option value="">All Sources</option>
                        <option value="purchase" @selected(request('source_type') === 'purchase')>Purchase</option>
                        <option value="buyer_supplied" @selected(request('source_type') === 'buyer_supplied')>Buyer Supplied</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-2">
                    <select name="item_id" class="form-control inv-select2">
                        <option value="">All Items</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mt-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-2 mt-2">
                    <a href="{{ route('inventory.grns.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>GRN Number</th><th>Source</th><th>PO Number</th><th>Store</th><th>Supplier / Buyer</th><th>Receive Date</th><th>Items</th><th>Total</th><th>Status</th><th>Created Date</th><th>Created By</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($grns as $grn)
                            <tr>
                                <td>{{ $loop->iteration + $grns->firstItem() - 1 }}</td>
                                <td><a href="{{ route('inventory.grns.show', $grn) }}">{{ $grn->grn_number }}</a></td>
                                <td>
                                    @if($grn->source_type === 'buyer_supplied')
                                        <span class="badge p-1 text-white bg-info">Buyer Supplied</span>
                                    @else
                                        <span class="badge p-1 text-white bg-secondary">Purchase</span>
                                    @endif
                                </td>
                                <td>{{ $grn->purchaseOrder?->po_number ?? '—' }}</td>
                                <td>{{ $grn->store?->name }}</td>
                                <td>
                                    @if($grn->source_type === 'buyer_supplied')
                                        {{ $grn->buyer?->name }}
                                        @if($grn->style)<br><small class="text-muted">{{ $grn->style }}</small>@endif
                                    @else
                                        {{ $grn->supplier?->name }}
                                    @endif
                                </td>
                                <td>{{ $grn->receive_date?->format('d M Y') }}</td>
                                <td>{{ $grn->items_count }}</td>
                                <td>{{ inv_qty($grn->total_amount) }}</td>
                                <td><span class="badge p-1 text-white bg-success">{{ ucfirst($grn->status) }}</span></td>
                                <td>{{ $grn->created_at?->format('d M Y, h:i A') }}</td>
                                <td>{{ $grn->creator?->name ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('inventory.grns.show', $grn) }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a>
                                    @can('inv_grn.edit')
                                        <a href="{{ route('inventory.grns.edit', $grn) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('inv_grn.delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteGrnModal" data-action="{{ route('inventory.grns.destroy', $grn) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="text-center text-muted">No GRNs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $grns->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteGrnModal', 'label' => 'GRN'])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
