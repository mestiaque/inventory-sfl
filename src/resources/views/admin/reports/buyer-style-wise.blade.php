@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Buyer & Style Wise Report') }}
    @else
        <title>{{ websiteTitle('Buyer & Style Wise Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Buyer & Style Wise Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Buyer &amp; Style Wise Report</h5>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'buyer-style-wise'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-2">
                        <input type="text" name="style" class="form-control" value="{{ request('style') }}" placeholder="Search style">
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
                        <select name="item_id" class="form-control inv-select2">
                            <option value="">All Items</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="Receive From"></div>
                    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="Receive To"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-secondary w-100">Filter</button></div>
                </form>
                <p class="text-muted mb-3" style="font-size:12px;">Search by style alone, buyer alone, item alone, or any combination — Issue/Delivery Qty and Balance always reflect the full history for that buyer + style + item, not just the date range above (which only narrows which received lots are listed). Issue/Delivery Qty only counts <strong>Approved</strong> challans — a Prepared or Authorized-but-not-yet-approved challan hasn't actually moved stock out yet, so it's not counted here.</p>
            @endunless

            <style>
                .inv-grouped-table > tbody > tr.inv-group-start > td { border-top: 2px solid var(--inv-accent, #f97316); }
                .inv-grouped-table > tbody > tr > td[rowspan] { background: #fff8ed; vertical-align: middle; }
            </style>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle inv-grouped-table">
                    <thead>
                        <tr>
                            <th>Style</th><th>Buyer</th><th style="min-width:200px">Item</th><th>Unit</th><th>Color</th><th>Size</th>
                            <th class="text-end">Received Qty</th><th class="text-end">Rejected Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th>
                            <th>Lot No</th><th>Batch No</th><th>Expiry Date</th>
                            <th class="text-end">Issue/Delivery Qty</th><th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $i => $line)
                            <tr @class(['inv-group-start' => $styleSpans[$i] > 0 && $i > 0])>
                                @if($styleSpans[$i] > 0)
                                    <td rowspan="{{ $styleSpans[$i] }}" class="fw-bold align-middle">{{ $line->grn?->style ?: '—' }}</td>
                                @endif
                                @if($buyerSpans[$i] > 0)
                                    <td rowspan="{{ $buyerSpans[$i] }}" class="align-middle">{{ $line->grn?->buyer?->name ?? '—' }}</td>
                                @endif
                                <td>{{ $line->item?->item_code }} — {{ $line->item?->item_name }}</td>
                                <td>{{ $line->item?->unit?->short_name }}</td>
                                <td>{{ $line->color?->name ?? '—' }}</td>
                                <td>{{ $line->size?->name ?? '—' }}</td>
                                <td class="text-end">{{ inv_qty($line->received_qty) }}</td>
                                <td class="text-end">{{ inv_qty($line->rejected_qty) }}</td>
                                <td class="text-end">{{ inv_qty($line->rate) }}</td>
                                <td class="text-end">{{ inv_qty($line->amount) }}</td>
                                <td>{{ $line->lot_no ?? '—' }}</td>
                                <td>{{ $line->batch_no ?? '—' }}</td>
                                <td>{{ optional($line->expiry_date)->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">{{ inv_qty($line->item_total_issued) }}</td>
                                <td class="text-end">
                                    <span class="badge p-1 text-white bg-{{ $line->item_balance > 0 ? 'success' : ($line->item_balance < 0 ? 'danger' : 'secondary') }}">{{ inv_qty($line->item_balance) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="15" class="text-center text-muted">No buyer/style receipts found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="6" class="text-end">Total</td>
                                <td class="text-end">{{ inv_qty($rows->sum('received_qty')) }}</td>
                                <td class="text-end">{{ inv_qty($rows->sum('rejected_qty')) }}</td>
                                <td></td>
                                <td class="text-end">{{ inv_qty($rows->sum('amount')) }}</td>
                                <td colspan="5"></td>
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
