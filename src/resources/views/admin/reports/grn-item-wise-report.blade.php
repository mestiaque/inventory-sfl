@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Item Wise Goods Receive Report') }}
    @else
        <title>{{ websiteTitle('Item Wise Goods Receive Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Item Wise Goods Receive Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Item Wise Goods Receive Report</h5>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'grn-item-wise'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <select name="item_id" class="form-control inv-select2">
                            <option value="">All Items</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
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
                        <select name="supplier_id" class="form-control inv-select2">
                            <option value="">All Suppliers</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="Receive From"></div>
                    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="Receive To"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-secondary w-100">Filter</button></div>
                </form>
            @endunless

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead>
                        <tr>
                            <th>GRN No</th><th>PO No</th><th>Item Code</th><th>Item Name</th><th>Store</th>
                            <th>Supplier / Buyer</th><th class="text-end">Qty</th><th>Unit</th><th class="text-end">Rate</th><th class="text-end">Amount</th>
                            <th>Receive Date</th><th>Created Date</th><th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lines as $line)
                            <tr>
                                <td>{{ $line->grn?->grn_number }}</td>
                                <td>{{ $line->grn?->purchaseOrder?->po_number ?? '—' }}</td>
                                <td>{{ $line->item?->item_code }}</td>
                                <td>{{ $line->item?->item_name }}</td>
                                <td>{{ $line->grn?->store?->name }}</td>
                                <td>{{ $line->grn?->source_type === 'buyer_supplied' ? $line->grn?->buyer?->name : $line->grn?->supplier?->name }}</td>
                                <td class="text-end">{{ inv_qty($line->received_qty) }}</td>
                                <td>{{ $line->item?->unit?->short_name }}</td>
                                <td class="text-end">{{ inv_qty($line->rate) }}</td>
                                <td class="text-end">{{ inv_qty($line->amount) }}</td>
                                <td>{{ $line->grn?->receive_date?->format('d M Y') }}</td>
                                <td>{{ $line->created_at?->format('d M Y, h:i A') }}</td>
                                <td>{{ $line->grn?->creator?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="text-center text-muted">No goods receive lines found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($lines->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold"><td colspan="9" class="text-end">Total</td><td class="text-end">{{ inv_qty($lines->sum('amount')) }}</td><td colspan="3"></td></tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @unless($printMode)
                {{ $lines->links('pagination::bootstrap-5') }}
            @endunless
        </div>
    </div>
</div>
@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
