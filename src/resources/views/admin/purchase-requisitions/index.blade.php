@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Purchase Requisitions') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Purchase Requisitions</h5>
            <div>
                @can('inv_purchase_requisition.delete')
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#purchaseRequisitionTrashModal">
                        <i class="fa-solid fa-trash"></i> Trash ({{ $trashedPurchaseRequisitions->count() }})
                    </button>
                @endcan
                @can('inv_purchase_requisition.add')
                    <a href="{{ route('inventory.purchase-requisitions.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Purchase Requisition</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search requisition no" value="{{ request('search') }}">
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
                    <select name="status" class="form-control inv-select2">
                        <option value="">All Status</option>
                        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'converted' => 'Converted', 'partially_converted' => 'Partially Converted'] as $value => $label)
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
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-2 mt-2">
                    <a href="{{ route('inventory.purchase-requisitions.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Requisition No</th><th>Department</th><th>Date</th><th>Requested By</th><th class="text-center">Items</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseRequisitions as $purchaseRequisition)
                            <tr>
                                <td>{{ $loop->iteration + $purchaseRequisitions->firstItem() - 1 }}</td>
                                <td>{{ $purchaseRequisition->requisition_no }}</td>
                                <td>{{ $purchaseRequisition->department?->name ?? '—' }}</td>
                                <td>{{ $purchaseRequisition->requisition_date?->format('d M Y') }}</td>
                                <td>{{ $purchaseRequisition->requester?->name ?? '—' }}</td>
                                <td class="text-center">{{ $purchaseRequisition->items->count() }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'approved' => 'info', 'rejected' => 'danger', 'converted' => 'success', 'partially_converted' => 'warning'][$purchaseRequisition->status] ?? 'secondary' }}">
                                        {{ ucwords(str_replace('_', ' ', $purchaseRequisition->status)) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#viewPreqModal{{ $purchaseRequisition->id }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @if($purchaseRequisition->status === 'pending')
                                        @can('inv_purchase_requisition.edit')
                                            <a href="{{ route('inventory.purchase-requisitions.edit', $purchaseRequisition) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                        @endcan
                                        @can('inv_purchase_requisition.approve')
                                            <a href="{{ route('inventory.purchase-requisitions.approval-form', $purchaseRequisition) }}" class="btn btn-sm btn-outline-success">Approve/Reject</a>
                                        @endcan
                                        @can('inv_purchase_requisition.delete')
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deletePreqModal" data-action="{{ route('inventory.purchase-requisitions.destroy', $purchaseRequisition) }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        @endcan
                                    @endif
                                    @can('inv_purchase_order.add')
                                        @if(in_array($purchaseRequisition->status, ['approved', 'partially_converted']))
                                            <a href="{{ route('inventory.purchase-orders.create', ['purchase_requisition_id' => $purchaseRequisition->id]) }}" class="btn btn-sm btn-outline-secondary">Create Purchase Order</a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No purchase requisitions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $purchaseRequisitions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- View modals live outside the table — a <div> can't legally be a direct child of <tbody>, and browsers "fix" that by relocating it, which corrupts the table nested inside the modal and makes it render as plain page content instead of a floating overlay. --}}
@foreach($purchaseRequisitions as $purchaseRequisition)
    <div class="modal fade" id="viewPreqModal{{ $purchaseRequisition->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Purchase Requisition Details — {{ $purchaseRequisition->requisition_no }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-3">Department</dt><dd class="col-sm-9">{{ $purchaseRequisition->department?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Requisition Date</dt><dd class="col-sm-9">{{ $purchaseRequisition->requisition_date?->format('d M Y') }}</dd>
                        <dt class="col-sm-3">Requested By</dt><dd class="col-sm-9">{{ $purchaseRequisition->requester?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'approved' => 'info', 'rejected' => 'danger', 'converted' => 'success', 'partially_converted' => 'warning'][$purchaseRequisition->status] ?? 'secondary' }}">
                                {{ ucwords(str_replace('_', ' ', $purchaseRequisition->status)) }}
                            </span>
                        </dd>
                        @if($purchaseRequisition->approver)
                            <dt class="col-sm-3">{{ $purchaseRequisition->status === 'rejected' ? 'Rejected By' : 'Approved By' }}</dt>
                            <dd class="col-sm-9">{{ $purchaseRequisition->approver->name }} @if($purchaseRequisition->approved_at)<span class="text-muted">({{ $purchaseRequisition->approved_at->format('d M Y h:i A') }})</span>@endif</dd>
                        @endif
                        @if($purchaseRequisition->approval_remarks)
                            <dt class="col-sm-3">Approval Remarks</dt><dd class="col-sm-9">{{ $purchaseRequisition->approval_remarks }}</dd>
                        @endif
                        <dt class="col-sm-3">Remarks</dt><dd class="col-sm-9">{{ $purchaseRequisition->remarks ?: '—' }}</dd>
                    </dl>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th><th>Item</th><th>Unit</th><th>Color</th><th>Size</th>
                                    <th class="text-end">Requested</th>
                                    <th class="text-end">Approved</th>
                                    <th class="text-end">Converted</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseRequisition->items as $line)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $line->item?->item_code }} — {{ $line->item?->item_name }}</td>
                                        <td>{{ $line->item?->unit?->short_name ?? '—' }}</td>
                                        <td>{{ $line->color?->name ?? '—' }}</td>
                                        <td>{{ $line->size?->name ?? '—' }}</td>
                                        <td class="text-end">{{ inv_qty($line->requested_qty) }}</td>
                                        <td class="text-end">{{ inv_qty($line->approved_qty) }}</td>
                                        <td class="text-end">{{ inv_qty($line->converted_qty) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deletePreqModal', 'label' => 'purchase requisition'])
@include('sfl-inventory::admin.partials.trash-modal', [
    'modalId' => 'purchaseRequisitionTrashModal',
    'label' => 'Purchase Requisition',
    'rows' => $trashedPurchaseRequisitions,
    'restoreRoute' => 'inventory.purchase-requisitions.restore',
    'forceRoute' => 'inventory.purchase-requisitions.force-destroy',
    'canRestore' => auth()->user()->can('inv_purchase_requisition.delete'),
    'canForce' => auth()->user()->can('inv_purchase_requisition.force_delete'),
    'forceWarning' => 'If any real Purchase Order was created against this requisition, that order is kept — it just loses its link back to this requisition.',
])

@include('sfl-inventory::admin.partials.select2-init')
@endsection
