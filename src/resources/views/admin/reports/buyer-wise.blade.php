@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Buyer Wise Report') }}
    @else
        <title>{{ websiteTitle('Buyer Wise Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Buyer Wise Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Buyer Wise Report</h5>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'buyer-wise'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <select name="buyer_id" class="form-control inv-select2">
                            <option value="">All Buyers</option>
                            @foreach($buyers as $buyer)
                                <option value="{{ $buyer->id }}" @selected(request('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="item_id" class="form-control inv-select2">
                            <option value="">All Items</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From"></div>
                    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-secondary w-100">Filter</button></div>
                </form>
            @endunless

            <h6>Summary — Received from Buyer (Buyer Supplied Goods Receive)</h6>
            <div class="table-responsive mb-2">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Buyer</th><th>Style(s)</th>
                            <th class="text-end">GRNs</th><th class="text-end">Items</th>
                            <th>First Receive</th><th>Last Receive</th>
                            <th class="text-end">Total Received Qty</th><th class="text-end">Total Rejected Qty</th><th class="text-end">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receivedSummary as $row)
                            <tr>
                                <td>{{ $row->buyer_name }}</td>
                                <td>{{ $row->styles ?: '—' }}</td>
                                <td class="text-end">{{ $row->grn_count }}</td>
                                <td class="text-end">{{ $row->item_count }}</td>
                                <td>{{ $row->first_receive_date ? \Carbon\Carbon::parse($row->first_receive_date)->format('d M Y') : '—' }}</td>
                                <td>{{ $row->last_receive_date ? \Carbon\Carbon::parse($row->last_receive_date)->format('d M Y') : '—' }}</td>
                                <td class="text-end">{{ inv_qty($row->total_qty) }}</td>
                                <td class="text-end">{{ inv_qty($row->total_rejected_qty) }}</td>
                                <td class="text-end">{{ inv_qty($row->total_amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No buyer supplied receipts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h6 class="mt-4">Detail — Received from Buyer</h6>
            <div class="table-responsive mb-2">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead>
                        <tr>
                            <th>GRN No</th><th>Receive Date</th><th>Buyer</th><th>Style</th><th>Order Ref</th>
                            <th>Item Code</th><th>Item Name</th><th>Color</th><th>Size</th><th>Store</th><th>Unit</th>
                            <th class="text-end">Received Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th>
                            <th>Lot No</th><th>Batch No</th><th>Expiry Date</th><th>Challan/Invoice No</th><th>Received By</th><th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($received as $line)
                            <tr>
                                <td>{{ $line->grn?->grn_number }}</td>
                                <td>{{ $line->grn?->receive_date?->format('d M Y') }}</td>
                                <td>{{ $line->grn?->buyer?->name }}</td>
                                <td>{{ $line->grn?->style ?? '—' }}</td>
                                <td>{{ $line->grn?->order_ref ?? '—' }}</td>
                                <td>{{ $line->item?->item_code }}</td>
                                <td>{{ $line->item?->item_name }}</td>
                                <td>{{ $line->color?->name ?? '—' }}</td>
                                <td>{{ $line->size?->name ?? '—' }}</td>
                                <td>{{ $line->grn?->store?->name }}</td>
                                <td>{{ $line->item?->unit?->short_name }}</td>
                                <td class="text-end">{{ inv_qty($line->received_qty) }}</td>
                                <td class="text-end">{{ inv_qty($line->rate) }}</td>
                                <td class="text-end">{{ inv_qty($line->amount) }}</td>
                                <td>{{ $line->lot_no ?? '—' }}</td>
                                <td>{{ $line->batch_no ?? '—' }}</td>
                                <td>{{ optional($line->expiry_date)->format('d M Y') ?? '—' }}</td>
                                <td>{{ $line->grn?->challan_invoice_no ?? '—' }}</td>
                                <td>{{ $line->grn?->receiver?->name ?? '—' }}</td>
                                <td>{{ $line->grn?->creator?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="20" class="text-center text-muted">No buyer supplied receipts found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($received->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold"><td colspan="11" class="text-end">Total</td><td class="text-end">{{ inv_qty($received->sum('received_qty')) }}</td><td></td><td class="text-end">{{ inv_qty($received->sum('amount')) }}</td><td colspan="6"></td></tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @unless($printMode)
                {{ $received->links('pagination::bootstrap-5') }}
            @endunless

            <h6 class="mt-4">Summary — Issued to Production</h6>
            <div class="table-responsive mb-2">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Buyer</th><th>Style(s)</th>
                            <th class="text-end">Issues</th><th class="text-end">Items</th>
                            <th>First Issue</th><th>Last Issue</th>
                            <th class="text-end">Total Issued Qty</th><th class="text-end">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($issuedSummary as $row)
                            <tr>
                                <td>{{ $row->buyer_name }}</td>
                                <td>{{ $row->styles ?: '—' }}</td>
                                <td class="text-end">{{ $row->issue_count }}</td>
                                <td class="text-end">{{ $row->item_count }}</td>
                                <td>{{ $row->first_issue_date ? \Carbon\Carbon::parse($row->first_issue_date)->format('d M Y') : '—' }}</td>
                                <td>{{ $row->last_issue_date ? \Carbon\Carbon::parse($row->last_issue_date)->format('d M Y') : '—' }}</td>
                                <td class="text-end">{{ inv_qty($row->total_qty) }}</td>
                                <td class="text-end">{{ inv_qty($row->total_amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No issues found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h6 class="mt-4">Detail — Issued to Production</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Issue No</th><th>Issue Date</th><th>Buyer</th><th>Style</th><th>Order Ref</th><th>Department</th>
                            <th>Item Code</th><th>Item Name</th><th>Color</th><th>Size</th><th>Store</th><th>Unit</th>
                            <th class="text-end">Issued Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th>
                            <th>Status</th><th>Issued By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($issued as $line)
                            <tr>
                                <td>{{ $line->issue?->issue_no }}</td>
                                <td>{{ $line->issue?->issue_date?->format('d M Y') }}</td>
                                <td>{{ $line->issue?->buyer?->name }}</td>
                                <td>{{ $line->issue?->style ?? '—' }}</td>
                                <td>{{ $line->issue?->order_ref ?? '—' }}</td>
                                <td>{{ $line->issue?->department?->name ?? '—' }}</td>
                                <td>{{ $line->item?->item_code }}</td>
                                <td>{{ $line->item?->item_name }}</td>
                                <td>{{ $line->color?->name ?? '—' }}</td>
                                <td>{{ $line->size?->name ?? '—' }}</td>
                                <td>{{ $line->issue?->store?->name }}</td>
                                <td>{{ $line->item?->unit?->short_name }}</td>
                                <td class="text-end">{{ inv_qty($line->issued_qty) }}</td>
                                <td class="text-end">{{ inv_qty($line->unit_rate) }}</td>
                                <td class="text-end">{{ inv_qty($line->amount) }}</td>
                                <td>{{ ucfirst($line->issue?->status ?? '—') }}</td>
                                <td>{{ $line->issue?->issuer?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="17" class="text-center text-muted">No issues found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($issued->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold"><td colspan="12" class="text-end">Total</td><td class="text-end">{{ inv_qty($issued->sum('issued_qty')) }}</td><td></td><td class="text-end">{{ inv_qty($issued->sum('amount')) }}</td><td colspan="2"></td></tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @unless($printMode)
                {{ $issued->links('pagination::bootstrap-5') }}
            @endunless
        </div>
    </div>
</div>
@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
