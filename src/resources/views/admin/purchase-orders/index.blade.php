@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Purchase Orders') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Purchase Orders</h5>
            @can('inv_purchase_order.add')
                <a href="{{ route('inventory.purchase-orders.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Purchase Order</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search PO number" value="{{ request('search') }}">
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
                    <select name="status" class="form-control inv-select2">
                        <option value="">All Status</option>
                        @foreach(['draft' => 'Draft', 'approved' => 'Approved', 'received' => 'Received', 'closed' => 'Closed', 'cancelled' => 'Cancelled'] as $value => $label)
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
                <div class="col-md-1">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('inventory.purchase-orders.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>PO Number</th><th>Supplier</th><th>Order Date</th><th>Expected Date</th><th>Items</th><th>Total</th><th>Status</th><th>Created Date</th><th>Created By</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrders as $po)
                            <tr>
                                <td>{{ $loop->iteration + $purchaseOrders->firstItem() - 1 }}</td>
                                <td>{{ $po->po_number }}</td>
                                <td>{{ $po->supplier?->name }}</td>
                                <td>{{ $po->order_date?->format('d M Y') }}</td>
                                <td>{{ $po->expected_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $po->items_count }}</td>
                                <td>{{ number_format($po->total_amount, 2) }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ ['draft' => 'secondary', 'approved' => 'info', 'received' => 'primary', 'closed' => 'success', 'cancelled' => 'danger'][$po->status] ?? 'secondary' }}">
                                        {{ ucfirst($po->status) }}
                                    </span>
                                </td>
                                <td>{{ $po->created_at?->format('d M Y, h:i A') }}</td>
                                <td>{{ $po->creator?->name ?? '—' }}</td>
                                <td class="text-end">
                                    @can('inv_purchase_order.view')
                                        <a href="{{ route('inventory.purchase-orders.show', $po) }}" class="btn btn-sm btn-outline-secondary" title="Challans ({{ $po->grns_count }})">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    @endcan
                                    @if($po->status === 'draft')
                                        @can('inv_purchase_order.edit')
                                            <a href="{{ route('inventory.purchase-orders.edit', $po) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        @endcan
                                        @can('inv_purchase_order.approve')
                                            <form method="POST" action="{{ route('inventory.purchase-orders.approve', $po) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Approve" onclick="return confirm('Approve this purchase order?')"><i class="fa-solid fa-check"></i></button>
                                            </form>
                                        @endcan
                                        @can('inv_purchase_order.delete')
                                            <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" data-toggle="modal" data-target="#deletePoModal" data-action="{{ route('inventory.purchase-orders.destroy', $po) }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        @endcan
                                    @endif
                                    @can('inv_grn.add')
                                        @if(in_array($po->status, ['approved', 'received']))
                                            <a href="{{ route('inventory.grns.create-purchase', ['purchase_order_id' => $po->id]) }}" class="btn btn-sm btn-outline-secondary" title="Receive (GRN)">
                                                <i class="fa-solid fa-truck-ramp-box"></i>
                                            </a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-muted">No purchase orders found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $purchaseOrders->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deletePoModal', 'label' => 'purchase order'])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
