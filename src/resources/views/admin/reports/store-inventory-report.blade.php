@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Store Inventory Report') }}
    @else
        <title>{{ websiteTitle('Store Inventory Report') }}</title>
    @endif
@endsection

@push('css')
<style>
    /* This report's table is unusually wide (15+ columns, one per department) —
       the shared .table-responsive rule clips overflow entirely instead of
       scrolling it, so override to a real horizontal scrollbar here only. */
    /* .inv-module .store-inventory-scroll { overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; } */
</style>
@endpush

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Store Inventory Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Store Inventory Report</h5>
                <small class="text-muted">One row per stock movement — stock-in and section-wise issue quantities, with a running balance per item.</small>
            </div>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'store-inventory-report'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From"></div>
                    <div class="col-md-3"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To"></div>
                    <div class="col-md-3"><button type="submit" class="btn btn-secondary">Filter</button></div>
                </form>
            @endunless

            <div class="table-responsive store-inventory-scroll">
                <table class="table table-bordered table-striped table-sm align-middle" style="font-size:12px; min-width:1400px;">
                    <thead class="text-center">
                        <tr>
                            <th rowspan="2">#</th>
                            <th rowspan="2">Item</th>
                            <th rowspan="2">Item Code</th>
                            <th rowspan="2">Category</th>
                            <th rowspan="2">Store</th>
                            <th rowspan="2">Date</th>
                            <th rowspan="2">Invoice/<br>Challan No.</th>
                            <th rowspan="2">Stock In<br>Qty</th>
                            <th colspan="{{ $departments->count() }}">Issue Qty (by Section)</th>
                            <th rowspan="2">Balance<br>Stock Qty</th>
                            <th rowspan="2">Unit</th>
                            <th rowspan="2">Cost Per<br>Unit</th>
                            <th rowspan="2">Total<br>Value</th>
                            <th rowspan="2">Received By</th>
                            <th rowspan="2">Issued By</th>
                        </tr>
                        <tr>
                            @foreach($departments as $department)
                                <th>{{ $department->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="text-start">{{ $row->item_name }}</td>
                                <td>{{ $row->item_code }}</td>
                                <td>{{ $row->category_name }}</td>
                                <td>{{ $row->store_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->transaction_date)->format('d-m-y') }}</td>
                                <td>{{ $row->challan_invoice_no }}</td>
                                <td class="text-end text-success">{{ $row->transaction_type === 'grn' && $row->qty_in > 0 ? inv_qty($row->qty_in) : '' }}</td>
                                @foreach($departments as $department)
                                    <td class="text-end text-danger">
                                        {{ $row->transaction_type === 'issue' && $row->department_name === $department->name ? inv_qty($row->qty_out) : '' }}
                                    </td>
                                @endforeach
                                <td class="text-end fw-bold">{{ inv_qty($row->running_balance) }}</td>
                                <td>{{ $row->unit }}</td>
                                <td class="text-end">{{ number_format($row->rate, 2) }}</td>
                                <td class="text-end">{{ number_format($row->value, 2) }}</td>
                                <td>{{ $row->received_by_name }}</td>
                                <td>{{ $row->issued_by_name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ 15 + $departments->count() }}" class="text-center text-muted">No stock movements found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-muted mb-0" style="font-size:12px;">Showing the latest 500 movements. Use the date filter to narrow the range.</p>
        </div>
    </div>
</div>
@endsection
