@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Store Requisitions') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Store Requisitions</h5>
            @can('inv_requisition.add')
                <a href="{{ route('inventory.requisitions.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Requisition</a>
            @endcan
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
                    <select name="buyer_id" class="form-control inv-select2">
                        <option value="">All Buyers</option>
                        @foreach($buyers as $buyer)
                            <option value="{{ $buyer->id }}" @selected(request('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control inv-select2">
                        <option value="">All Status</option>
                        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'issued' => 'Issued', 'partially_issued' => 'Partially Issued'] as $value => $label)
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
                    <a href="{{ route('inventory.requisitions.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Requisition No</th><th>Department</th><th>Store</th><th>Buyer / Style</th><th>Date</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($requisitions as $requisition)
                            <tr>
                                <td>{{ $loop->iteration + $requisitions->firstItem() - 1 }}</td>
                                <td>{{ $requisition->requisition_no }}</td>
                                <td>{{ $requisition->department?->name }}</td>
                                <td>{{ $requisition->store?->name }}</td>
                                <td>
                                    {{ $requisition->buyer?->name ?? '—' }}
                                    @if($requisition->style)<br><small class="text-muted">{{ $requisition->style }}</small>@endif
                                </td>
                                <td>{{ $requisition->requisition_date?->format('d M Y') }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'approved' => 'info', 'rejected' => 'danger', 'issued' => 'success', 'partially_issued' => 'warning'][$requisition->status] ?? 'secondary' }}">
                                        {{ ucwords(str_replace('_', ' ', $requisition->status)) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#viewReqModal{{ $requisition->id }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @if($requisition->status === 'pending')
                                        @can('inv_requisition.edit')
                                            <a href="{{ route('inventory.requisitions.edit', $requisition) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                        @endcan
                                        @can('inv_requisition.approve')
                                            <a href="{{ route('inventory.requisitions.approval-form', $requisition) }}" class="btn btn-sm btn-outline-success">Approve/Reject</a>
                                        @endcan
                                        @can('inv_requisition.delete')
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteReqModal" data-action="{{ route('inventory.requisitions.destroy', $requisition) }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        @endcan
                                    @endif
                                    @can('inv_issue.add')
                                        @if(in_array($requisition->status, ['approved', 'partially_issued']))
                                            <a href="{{ route('inventory.issues.create', ['requisition_id' => $requisition->id]) }}" class="btn btn-sm btn-outline-secondary">Issue</a>
                                        @endif
                                    @endcan
                                    @can('inv_requisition.print')
                                        <a href="{{ route('inventory.requisitions.print', $requisition) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-print"></i></a>
                                    @endcan
                                    @can('inv_requisition.force_delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger d-none" title="Force Delete" data-toggle="modal" data-target="#forceDeleteReqModal" data-action="{{ route('inventory.requisitions.force-destroy', $requisition) }}" data-req-name="{{ $requisition->requisition_no }}">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No requisitions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $requisitions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- View modals live outside the table — a <div> can't legally be a direct child of <tbody>, and browsers "fix" that by relocating it, which corrupts the table nested inside the modal and makes it render as plain page content instead of a floating overlay. --}}
@foreach($requisitions as $requisition)
    <div class="modal fade" id="viewReqModal{{ $requisition->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Requisition Details — {{ $requisition->requisition_no }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-3">Department</dt><dd class="col-sm-9">{{ $requisition->department?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Issue From Store</dt><dd class="col-sm-9">{{ $requisition->store?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Requisition Date</dt><dd class="col-sm-9">{{ $requisition->requisition_date?->format('d M Y') }}</dd>
                        <dt class="col-sm-3">Requisition For</dt><dd class="col-sm-9">{{ $requisition->requisition_for ? ucwords(str_replace('_', ' ', $requisition->requisition_for)) : '—' }}</dd>
                        <dt class="col-sm-3">Buyer</dt><dd class="col-sm-9">{{ $requisition->buyer?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Style / Order Ref</dt><dd class="col-sm-9">{{ collect([$requisition->style, $requisition->order_ref])->filter()->implode(' / ') ?: '—' }}</dd>
                        <dt class="col-sm-3">Requested By</dt><dd class="col-sm-9">{{ $requisition->requester?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'approved' => 'info', 'rejected' => 'danger', 'issued' => 'success', 'partially_issued' => 'warning'][$requisition->status] ?? 'secondary' }}">
                                {{ ucwords(str_replace('_', ' ', $requisition->status)) }}
                            </span>
                        </dd>
                        @if($requisition->approver)
                            <dt class="col-sm-3">{{ $requisition->status === 'rejected' ? 'Rejected By' : 'Approved By' }}</dt>
                            <dd class="col-sm-9">{{ $requisition->approver->name }} @if($requisition->approved_at)<span class="text-muted">({{ $requisition->approved_at->format('d M Y h:i A') }})</span>@endif</dd>
                        @endif
                        @if($requisition->approval_remarks)
                            <dt class="col-sm-3">Approval Remarks</dt><dd class="col-sm-9">{{ $requisition->approval_remarks }}</dd>
                        @endif
                        <dt class="col-sm-3">Remarks</dt><dd class="col-sm-9">{{ $requisition->remarks ?: '—' }}</dd>
                    </dl>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th><th>Item</th><th>Unit</th>
                                    <th class="text-end">Requested</th>
                                    <th class="text-end">Approved</th>
                                    <th class="text-end">Issued</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requisition->items as $line)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $line->item?->item_code }} — {{ $line->item?->item_name }}</td>
                                        <td>{{ $line->item?->unit?->short_name ?? '—' }}</td>
                                        <td class="text-end">{{ inv_qty($line->requested_qty) }}</td>
                                        <td class="text-end">{{ $line->approved_qty !== null ? inv_qty($line->approved_qty) : '—' }}</td>
                                        <td class="text-end">{{ inv_qty($line->issued_qty) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    @can('inv_requisition.print')
                        <a href="{{ route('inventory.requisitions.print', $requisition) }}" target="_blank" class="btn btn-outline-secondary"><i class="fa-solid fa-print"></i> Print</a>
                    @endcan
                    <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteReqModal', 'label' => 'requisition'])

<div class="modal fade" id="forceDeleteReqModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="forceDeleteReqModalForm">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Force Delete Requisition</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">Permanently delete <strong id="forceDeleteReqName"></strong>?</p>
                    <p class="text-danger mb-0">This works regardless of status. If any real Issue was made against this requisition, that Issue is kept — it just loses its link back to this requisition.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Force Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('js')
<script>
    $('#forceDeleteReqModal').on('show.bs.modal', function (event) {
        const trigger = $(event.relatedTarget);
        $('#forceDeleteReqModalForm').attr('action', trigger.data('action'));
        $('#forceDeleteReqName').text(trigger.data('req-name'));
    });
</script>
@endpush

@include('sfl-inventory::admin.partials.select2-init')
@endsection
