@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Broken Needle Machine Wise Report') }}
    @else
        <title>{{ websiteTitle('Broken Needle Machine Wise Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Broken Needle Machine Wise Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">
                    Broken Needle — Machine Wise Report
                    @if($machine)
                        <small class="text-muted">/ {{ $machine->name }} ({{ $machine->code }}) — Employee Breakdown</small>
                    @endif
                </h5>
                <small class="text-muted">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}, sorted {{ $sort === 'asc' ? 'low to high' : 'high to low' }}</small>
            </div>
            @unless($printMode)
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('inventory.broken-needles.machine-report.export', request()->query()) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel"></i> Excel</a>
                    <a href="{{ url()->current() }}?{{ http_build_query(array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-print"></i> Print</a>
                    <a href="{{ route('inventory.broken-needles.report') }}" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-chart-column"></i> Employee Report</a>
                    <a href="{{ route('inventory.broken-needles.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Entries</a>
                </div>
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                @if($machine)
                    <a href="{{ route('inventory.broken-needles.machine-report', array_merge(request()->except(['machine_id']))) }}" class="btn btn-sm btn-outline-secondary mb-3">
                        <i class="fa-solid fa-arrow-left"></i> Back to all machines
                    </a>
                @endif
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control" value="{{ $from }}" placeholder="From">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control" value="{{ $to }}" placeholder="To">
                    </div>
                    <div class="col-md-2">
                        <select name="department_id" class="form-control inv-select2">
                            <option value="">All Departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected($departmentId == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="machine_id" class="form-control inv-select2">
                            <option value="">All Machines (overview)</option>
                            @foreach($machines as $m)
                                <option value="{{ $m->id }}" @selected($machineId == $m->id)>{{ $m->name }} ({{ $m->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="sort" class="form-control inv-select2">
                            <option value="desc" @selected($sort === 'desc')>Qty: High to Low</option>
                            <option value="asc" @selected($sort === 'asc')>Qty: Low to High</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-secondary w-100">Filter</button>
                    </div>
                </form>
            @endunless

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead>
                        @if($machine)
                            <tr><th>#</th><th>Employee ID</th><th>Employee Name</th><th class="text-end">Incidents</th><th class="text-end">Total Broken Needle Qty</th></tr>
                        @else
                            <tr><th>#</th><th>Machine</th><th>Code</th><th>Department</th><th>Section</th><th>Line</th><th class="text-end">Incidents</th><th class="text-end">Total Broken Needle Qty</th></tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @if($machine)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->employee?->employee_id ?? $row->employee_id }}</td>
                                    <td>{{ $row->employee?->name ?? '—' }}</td>
                                    <td class="text-end">{{ $row->incidents }}</td>
                                    <td class="text-end fw-bold">{{ $row->total_qty }}</td>
                                </tr>
                            @else
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if($row->machine)
                                            @unless($printMode)
                                                <a href="{{ route('inventory.broken-needles.machine-report', array_merge(request()->except(['machine_id', 'page']), ['machine_id' => $row->machine->id])) }}">{{ $row->machine->name }}</a>
                                            @else
                                                {{ $row->machine->name }}
                                            @endif
                                        @else
                                            <span class="text-muted">Not Specified</span>
                                        @endif
                                    </td>
                                    <td>{{ $row->machine?->code ?? '—' }}</td>
                                    <td>{{ $row->machine?->department?->name ?? '—' }}</td>
                                    <td>{{ $row->machine?->section ?? '—' }}</td>
                                    <td>{{ $row->machine?->line ?? '—' }}</td>
                                    <td class="text-end">{{ $row->incidents }}</td>
                                    <td class="text-end fw-bold">{{ $row->total_qty }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="{{ $machine ? 5 : 8 }}" class="text-center text-muted">No broken needle entries for this period.</td></tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="{{ $machine ? 3 : 6 }}" class="text-end">Grand Total</td>
                                <td class="text-end">{{ $rows->sum('incidents') }}</td>
                                <td class="text-end">{{ $rows->sum('total_qty') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
