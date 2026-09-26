@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Store Order') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Store Order</h4>
            <div>
                @can('inv_purchase_order.delete')
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#poTrashModal">
                        <i class="fa-solid fa-trash"></i> Trash ({{ $trashedPurchaseOrders->count() }})
                    </button>
                @endcan
                @can('inv_purchase_requisition.list')
                    <a href="{{ route('inventory.purchase-requisitions.index') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Create from Purchase Requisition</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search Store Order number" value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="supplier_id" class="form-control form-control-sm inv-select2">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control form-control-sm inv-select2">
                        <option value="">All Status</option>
                        @foreach(['draft' => 'Draft', 'approved' => 'Approved', 'received' => 'Received', 'closed' => 'Closed', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="item_id" class="form-control form-control-sm inv-select2">
                        <option value="">All Items</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2 d-flex align-items-end flex-wrap gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                    <a href="{{ route('inventory.purchase-orders.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Store Order No</th><th>Supplier</th><th>Order Date</th><th>Expected Date</th><th>Items</th><th>Total (at order)</th><th>Status</th><th>Created Date</th><th>Created By</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrders as $po)
                            <tr>
                                <td>{{ $loop->iteration + $purchaseOrders->firstItem() - 1 }}</td>
                                <td>
                                    {{ $po->po_number }}
                                    @if($po->purchaseRequisition)
                                        <br><small class="text-muted">from {{ $po->purchaseRequisition->requisition_no }}</small>
                                    @endif
                                </td>
                                <td>{{ $po->supplier?->name }}</td>
                                <td>{{ $po->order_date?->format('d M Y') }}</td>
                                <td>{{ $po->expected_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $po->items_count }}</td>
                                <td>{{ inv_qty($po->total_amount) }}</td>
                                <td>
                                    <span class="badge badge-{{ ['draft' => 'secondary', 'approved' => 'info', 'received' => 'primary', 'closed' => 'success', 'cancelled' => 'danger'][$po->status] ?? 'secondary' }}">
                                        {{ ucfirst($po->status) }}
                                    </span>
                                </td>
                                <td>{{ $po->created_at?->format('d M Y, h:i A') }}</td>
                                <td>{{ $po->creator?->name ?? '—' }}</td>
                                <td class="text-right">
                                    @can('inv_purchase_order.view')
                                        <a href="{{ route('inventory.purchase-orders.show', $po) }}" class="btn-custom success" title="Challans ({{ $po->grns_count }})"><i class="fa-solid fa-eye"></i></a>
                                    @endcan
                                    @if($po->status === 'draft')
                                        @can('inv_purchase_order.edit')
                                            <a href="{{ route('inventory.purchase-orders.edit', $po) }}" class="btn-custom yellow" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        @endcan
                                        @can('inv_purchase_order.approve')
                                            <form method="POST" action="{{ route('inventory.purchase-orders.approve', $po) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn-custom success" title="Approve" onclick="return confirm('Approve this store order?')"><i class="fa-solid fa-check"></i></button>
                                            </form>
                                        @endcan
                                    @endif
                                    {{-- Store Orders now land as 'approved' straight away (draft is a legacy
                                         state), so Delete can't stay gated to status==='draft' — it would
                                         never show for any new order and nothing could ever reach Trash to
                                         be force-deleted. Gated on "no challans received against it yet"
                                         instead (same check destroy() itself already enforces server-side). --}}
                                    @can('inv_purchase_order.delete')
                                        @if($po->grns_count === 0)
                                            <button type="button" class="btn-custom danger" title="Delete" data-toggle="modal" data-target="#deletePoModal" data-action="{{ route('inventory.purchase-orders.destroy', $po) }}"><i class="fa-solid fa-trash"></i></button>
                                        @endif
                                    @endcan
                                    {{-- No grns_count gate here, unlike the soft-delete button above — safe
                                         even with challans already received, since both
                                         inv_grns.purchase_order_id and inv_grn_items.purchase_order_item_id
                                         are nullOnDelete: the GRNs and everything they already posted to
                                         the stock ledger stay completely intact, they just lose their
                                         back-link to this order. --}}
                                    @can('inv_purchase_order.force_delete')
                                        <button type="button" class="btn-custom danger" title="Delete Permanently" data-toggle="modal" data-target="#forcePoModal" data-action="{{ route('inventory.purchase-orders.force-destroy', $po) }}"><i class="fa-solid fa-triangle-exclamation"></i></button>
                                    @endcan
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
                            <tr><td colspan="11" class="text-center text-muted">No store orders found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $purchaseOrders->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deletePoModal', 'label' => 'store order'])

{{-- Direct permanent delete right from the main list — a one-click
     alternative to the usual soft-delete-then-force-delete-from-Trash
     dance, for a fresh/mistaken auto-created Store Order. Same
     grns_count===0 safety gate as the row's own soft-delete button above. --}}
@can('inv_purchase_order.force_delete')
    <div class="modal fade" id="forcePoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="forcePoModalForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Delete Permanently</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-danger mb-0">Permanently delete this store order? This cannot be undone — it skips Trash entirely.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm">Delete Permanently</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('js')
    <script>
        $('#forcePoModal').on('show.bs.modal', function (event) {
            $('#forcePoModalForm').attr('action', $(event.relatedTarget).data('action'));
        });
    </script>
    @endpush
@endcan

@include('sfl-inventory::admin.partials.trash-modal', [
    'modalId' => 'poTrashModal',
    'label' => 'Store Order',
    'rows' => $trashedPurchaseOrders,
    'restoreRoute' => 'inventory.purchase-orders.restore',
    'forceRoute' => 'inventory.purchase-orders.force-destroy',
    'canRestore' => auth()->user()->can('inv_purchase_order.delete'),
    'canForce' => auth()->user()->can('inv_purchase_order.force_delete'),
])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
