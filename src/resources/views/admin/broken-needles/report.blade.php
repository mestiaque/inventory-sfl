@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Broken Needle Monthly Report') }}
    @else
        <title>{{ websiteTitle('Broken Needle Monthly Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Broken Needle Monthly Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Broken Needle — Monthly Report</h5>
                <small class="text-muted">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}, sorted {{ $sort === 'asc' ? 'low to high' : 'high to low' }}</small>
            </div>
            @unless($printMode)
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('inventory.broken-needles.report.export', request()->query()) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel"></i> Excel</a>
                    <a href="{{ url()->current() }}?{{ http_build_query(array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-print"></i> Print</a>
                    <a href="{{ route('inventory.broken-needles.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Entries</a>
                </div>
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <input type="date" name="date_from" class="form-control" value="{{ $from }}" placeholder="From">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_to" class="form-control" value="{{ $to }}" placeholder="To">
                    </div>
                    <div class="col-md-3">
                        <select name="sort" class="form-control inv-select2">
                            <option value="desc" @selected($sort === 'desc')>Qty: High to Low</option>
                            <option value="asc" @selected($sort === 'asc')>Qty: Low to High</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-secondary w-100">Filter</button>
                    </div>
                </form>
            @endunless

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Employee ID</th><th>Employee Name</th><th class="text-end">Incidents</th><th class="text-end">Total Broken Needle Qty</th></tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $row->employee?->employee_id ?? $row->employee_id }}</td>
                                <td>{{ $row->employee?->name ?? '—' }}</td>
                                <td class="text-end">{{ $row->incidents }}</td>
                                <td class="text-end fw-bold">{{ $row->total_qty }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No broken needle entries for this period.</td></tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold"><td colspan="3" class="text-end">Grand Total</td><td class="text-end">{{ $rows->sum('incidents') }}</td><td class="text-end">{{ $rows->sum('total_qty') }}</td></tr>
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
