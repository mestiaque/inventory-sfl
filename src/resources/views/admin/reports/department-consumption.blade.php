@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Department Wise Consumption') }}
    @else
        <title>{{ websiteTitle('Department Wise Consumption') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Department Wise Consumption'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Department Wise Consumption</h5>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'department-consumption'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <select name="department_id" class="form-control inv-select2">
                            <option value="">All Departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From"></div>
                    <div class="col-md-3"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To"></div>
                    <div class="col-md-3"><button type="submit" class="btn btn-secondary">Filter</button></div>
                </form>
            @endunless

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead><tr><th>Department</th><th class="text-end">Consumed Qty</th><th class="text-end">Waste Qty</th></tr></thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->department_name }}</td>
                                <td class="text-end">{{ number_format($row->total_consumed, 4) }}</td>
                                <td class="text-end">{{ number_format($row->total_waste, 4) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No consumption records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
