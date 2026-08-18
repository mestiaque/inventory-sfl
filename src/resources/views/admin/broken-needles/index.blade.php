@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Broken Needle') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Broken Needle Entries</h5>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('inventory.broken-needles.daily-report') }}" class="btn btn-outline-warning btn-sm"><i class="fa-solid fa-file-lines"></i> Daily Needle Supply Report</a>
                <a href="{{ route('inventory.broken-needles.report') }}" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-chart-column"></i> Monthly Report</a>
                <a href="{{ route('inventory.broken-needles.machine-report') }}" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-industry"></i> Machine Report</a>
                @can('inv_broken_needle.add')
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createBrokenNeedleModal">
                        <i class="fa-solid fa-plus"></i> Add Entry
                    </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <select name="employee_id" class="form-control inv-select2">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->name }} ({{ $employee->employee_id }})</option>
                        @endforeach
                    </select>
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
                    <select name="machine_id" class="form-control inv-select2">
                        <option value="">All Machines</option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine->id }}" @selected(request('machine_id') == $machine->id)>{{ $machine->name }} ({{ $machine->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <input type="text" name="line_no" class="form-control" placeholder="Line No" value="{{ request('line_no') }}">
                </div>
                <div class="col-md-2">
                    <select name="buyer_id" class="form-control inv-select2">
                        <option value="">All Buyers</option>
                        @foreach($buyers as $buyer)
                            <option value="{{ $buyer->id }}" @selected(request('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-1">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-12">
                    <a href="{{ route('inventory.broken-needles.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th><th>Date</th><th>Line</th><th>Employee</th><th>Department</th><th>Machine</th>
                            <th>Needle Type</th><th>Needle Size</th><th class="text-end">Qty</th><th>Buyer / Style</th><th>Remarks</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            <tr>
                                <td>{{ $loop->iteration + $entries->firstItem() - 1 }}</td>
                                <td>{{ $entry->broken_date?->format('d M Y') }}</td>
                                <td>{{ $entry->line_no }}</td>
                                <td>{{ $entry->employee?->name ?? '—' }}</td>
                                <td>{{ $entry->department?->name ?? '—' }}</td>
                                <td>{{ $entry->machine?->name ?? '—' }}</td>
                                <td>{{ $entry->needle_type }}</td>
                                <td>{{ $entry->needle_size }}</td>
                                <td class="text-end">{{ $entry->quantity }}</td>
                                <td>{{ trim(($entry->buyer?->name ?? '') . ($entry->style ? ' / ' . $entry->style : '')) ?: '—' }}</td>
                                <td>{{ $entry->remarks }}</td>
                                <td class="text-end">
                                    @can('inv_broken_needle.edit')
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editBrokenNeedleModal{{ $entry->id }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    @endcan
                                    @can('inv_broken_needle.delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteBrokenNeedleModal" data-action="{{ route('inventory.broken-needles.destroy', $entry) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endcan
                                    @can('inv_broken_needle.force_delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger d-none" title="Force Delete (permanent)" data-toggle="modal" data-target="#forceDeleteBrokenNeedleModal" data-action="{{ route('inventory.broken-needles.force-destroy', $entry) }}">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>

                            @can('inv_broken_needle.edit')
                                <div class="modal fade" id="editBrokenNeedleModal{{ $entry->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('inventory.broken-needles.update', $entry) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Entry</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                </div>
                                                <div class="modal-body">
                                                    @include('sfl-inventory::admin.broken-needles.partials.fields', ['entry' => $entry])
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endcan
                        @empty
                            <tr><td colspan="12" class="text-center text-muted">No broken needle entries found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $entries->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@can('inv_broken_needle.add')
    <div class="modal fade" id="createBrokenNeedleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('inventory.broken-needles.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Broken Needle Entry</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @include('sfl-inventory::admin.broken-needles.partials.fields', ['entry' => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteBrokenNeedleModal', 'label' => 'entry'])

<div class="modal fade" id="forceDeleteBrokenNeedleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="forceDeleteBrokenNeedleModalForm">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Force Delete Entry</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger mb-0">Permanently delete this broken needle entry? This cannot be undone (unlike the regular delete, which can be recovered).</p>
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
    $('#forceDeleteBrokenNeedleModal').on('show.bs.modal', function (event) {
        $('#forceDeleteBrokenNeedleModalForm').attr('action', $(event.relatedTarget).data('action'));
    });
</script>
@endpush
@include('sfl-inventory::admin.partials.select2-init')
@endsection





