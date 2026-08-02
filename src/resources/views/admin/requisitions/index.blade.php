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

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteReqModal', 'label' => 'requisition'])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
