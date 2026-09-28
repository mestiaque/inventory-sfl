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
                <form method="GET" class="row mb-3 align-items-end">
                    <div class="col-md-3 mb-2">
                        <select name="item_id" class="form-control form-control-sm inv-select2">
                            <option value="">All Items</option>
                            @foreach($items as $filterItem)
                                <option value="{{ $filterItem->id }}" @selected(request('item_id') == $filterItem->id)>{{ $filterItem->item_code }} — {{ $filterItem->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="store_id" class="form-control form-control-sm inv-select2">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2"><input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From"></div>
                    <div class="col-md-3 mb-2"><input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To"></div>
                    <div class="col-md-3 mb-2 d-flex align-items-end flex-wrap gap-1">
                        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                        <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Reset</a>
                    </div>
                </form>
            @endunless

            <div class="table-responsive store-inventory-scroll">
                <table class="table table-bordered table-sm align-middle" style="font-size:12px; min-width:1400px;">
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
                            <th rowspan="2">Other Out<br>Qty</th>
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
                                <td>{{ ($rows->firstItem() ?? 1) + $loop->index }}</td>
                                <td class="text-left">{{ $row->item_name }}</td>
                                <td>{{ $row->item_code }}</td>
                                <td>{{ $row->category_name }}</td>
                                <td>{{ $row->store_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->transaction_date)->format('d-m-y') }}</td>
                                <td>{{ $row->challan_invoice_no }}</td>
                                <td class="text-right text-success" @if($row->qty_in > 0 && $row->transaction_type !== 'grn') title="{{ ucwords(str_replace('_', ' ', $row->transaction_type)) }}" @endif>{{ $row->qty_in > 0 ? inv_qty($row->qty_in) : '' }}</td>
                                @foreach($departments as $department)
                                    <td class="text-right text-danger">
                                        {{ $row->transaction_type === 'issue' && $row->department_name === $department->name ? inv_qty($row->qty_out) : '' }}
                                    </td>
                                @endforeach
                                @php $isSectionIssue = $row->transaction_type === 'issue' && $row->department_name; @endphp
                                <td class="text-right text-danger" title="{{ ucwords(str_replace('_', ' ', $row->transaction_type)) }}">{{ $row->qty_out > 0 && ! $isSectionIssue ? inv_qty($row->qty_out) : '' }}</td>
                                <td class="text-right font-weight-bold">{{ inv_qty($row->running_balance) }}</td>
                                <td>{{ $row->unit }}</td>
                                <td class="text-right">{{ inv_qty($row->rate) }}</td>
                                <td class="text-right">{{ inv_qty($row->value) }}</td>
                                <td>{{ $row->received_by_name }}</td>
                                <td>{{ $row->issued_by_name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ 16 + $departments->count() }}" class="text-center text-muted">No stock movements found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="7" class="text-right">Total{{ $rows->hasPages() ? ' (all pages)' : '' }}</td>
                                <td class="text-right text-success">{{ inv_qty($totals->qty_in) }}</td>
                                @foreach($departments as $department)
                                    <td class="text-right text-danger">{{ isset($departmentTotals[$department->name]) ? inv_qty($departmentTotals[$department->name]) : '' }}</td>
                                @endforeach
                                <td class="text-right text-danger">{{ inv_qty($totals->other_out) }}</td>
                                <td colspan="3"></td>
                                <td class="text-right">{{ inv_qty($totals->value) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @unless($printMode)
                {{ $rows->links('pagination::bootstrap-5') }}
            @endunless
        </div>
    </div>
</div>
@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
