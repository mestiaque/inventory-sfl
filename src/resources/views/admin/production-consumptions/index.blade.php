@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Production Consumption') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Production Consumption</h5>
            @can('inv_production.add')
                <a href="{{ route('inventory.production-consumptions.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Consumption</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search consumption no" value="{{ request('search') }}">
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
                    <select name="store_id" class="form-control inv-select2">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
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
                    <a href="{{ route('inventory.production-consumptions.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Consumption No</th><th>Department</th><th>Store</th><th>Style</th><th>Order Ref</th><th>Date</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($consumptions as $consumption)
                            <tr>
                                <td>{{ $loop->iteration + $consumptions->firstItem() - 1 }}</td>
                                <td>{{ $consumption->consumption_no }}</td>
                                <td>{{ $consumption->department?->name }}</td>
                                <td>{{ $consumption->store?->name }}</td>
                                <td>{{ $consumption->style }}</td>
                                <td>{{ $consumption->order_ref }}</td>
                                <td>{{ $consumption->consumption_date?->format('d M Y') }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#viewConsModal{{ $consumption->id }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @can('inv_production.delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteConsModal" data-action="{{ route('inventory.production-consumptions.destroy', $consumption) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No consumption records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $consumptions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- View modals live outside the table — a <div> can't legally be a direct child of <tbody>, and browsers "fix" that by relocating it, which corrupts the table nested inside the modal and makes it render as plain page content instead of a floating overlay. --}}
@foreach($consumptions as $consumption)
    <div class="modal fade" id="viewConsModal{{ $consumption->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Consumption Details — {{ $consumption->consumption_no }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-3">Department</dt><dd class="col-sm-9">{{ $consumption->department?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Store</dt><dd class="col-sm-9">{{ $consumption->store?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Consumption Date</dt><dd class="col-sm-9">{{ $consumption->consumption_date?->format('d M Y') }}</dd>
                        <dt class="col-sm-3">Style / Order Ref</dt><dd class="col-sm-9">{{ collect([$consumption->style, $consumption->order_ref])->filter()->implode(' / ') ?: '—' }}</dd>
                        <dt class="col-sm-3">Linked Issue</dt><dd class="col-sm-9">{{ $consumption->issue?->issue_no ?? '—' }}</dd>
                        <dt class="col-sm-3">Created By</dt><dd class="col-sm-9">{{ $consumption->creator?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Remarks</dt><dd class="col-sm-9">{{ $consumption->remarks ?: '—' }}</dd>
                    </dl>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th><th>Item</th><th>Unit</th>
                                    <th class="text-end">Consumed</th>
                                    <th class="text-end">Waste</th>
                                    <th class="text-end">Total Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($consumption->items as $line)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $line->item?->item_code }} — {{ $line->item?->item_name }}</td>
                                        <td>{{ $line->item?->unit?->short_name ?? '—' }}</td>
                                        <td class="text-end">{{ inv_qty($line->consumed_qty) }}</td>
                                        <td class="text-end">{{ inv_qty($line->waste_qty) }}</td>
                                        <td class="text-end">{{ inv_qty($line->consumed_qty + $line->waste_qty) }}</td>
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

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteConsModal', 'label' => 'consumption record'])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
