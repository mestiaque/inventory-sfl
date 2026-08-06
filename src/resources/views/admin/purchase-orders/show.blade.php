@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Purchase Order ' . $purchaseOrder->po_number) }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Purchase Order {{ $purchaseOrder->po_number }}</h5>
            <div>
                @can('inv_grn.add')
                    @if(in_array($purchaseOrder->status, ['approved', 'received']))
                        <a href="{{ route('inventory.grns.create-purchase', ['purchase_order_id' => $purchaseOrder->id]) }}" class="btn btn-success btn-sm">
                            <i class="fa-solid fa-truck-ramp-box"></i> Receive (New Challan / GRN)
                        </a>
                    @elseif($purchaseOrder->status === 'draft')
                        <span class="badge p-2 text-white bg-secondary" title="Approve this purchase order first before a challan can be received against it.">
                            <i class="fa-solid fa-lock"></i> Approve first to receive
                        </span>
                    @endif
                @endcan
                <a href="{{ route('inventory.purchase-orders.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3 mb-2"><strong>Supplier:</strong> {{ $purchaseOrder->supplier?->name }}</div>
                <div class="col-md-3 mb-2"><strong>Order Date:</strong> {{ $purchaseOrder->order_date?->format('d M Y') }}</div>
                <div class="col-md-3 mb-2"><strong>Expected Date:</strong> {{ $purchaseOrder->expected_date?->format('d M Y') ?? '—' }}</div>
                <div class="col-md-3 mb-2">
                    <strong>Status:</strong>
                    <span class="badge p-1 text-white bg-{{ ['draft' => 'secondary', 'approved' => 'info', 'received' => 'warning', 'closed' => 'success'][$purchaseOrder->status] ?? 'secondary' }}">
                        {{ ucfirst($purchaseOrder->status) }}
                    </span>
                </div>
                <div class="col-md-3 mb-2"><strong>Items:</strong> {{ $purchaseOrder->items->count() }}</div>
                <div class="col-md-3 mb-2"><strong>Created By:</strong> {{ $purchaseOrder->creator?->name ?? '—' }}</div>
                <div class="col-md-3 mb-2">
                    <strong>Approved By:</strong>
                    {{ $purchaseOrder->approver?->name ?? '—' }}
                    @if($purchaseOrder->approved_at)
                        <span class="text-muted">({{ $purchaseOrder->approved_at->format('d M Y h:i A') }})</span>
                    @endif
                </div>
                <div class="col-md-3 mb-2"><strong>Total Amount:</strong> {{ number_format($purchaseOrder->total_amount, 2) }}</div>
                <div class="col-12"><strong>Remarks:</strong> {{ $purchaseOrder->remarks ?: '—' }}</div>
            </div>

            <h6>Order Lines — Ordered vs. Received (across all challans)</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>Item</th><th>Unit</th><th class="text-end">Ordered</th><th class="text-end">Received (so far)</th><th class="text-end">Remaining</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->items as $line)
                            @php $remaining = $line->quantity - $line->received_qty; @endphp
                            <tr>
                                <td>{{ $line->item?->item_code }} — {{ $line->item?->item_name }}</td>
                                <td>{{ $line->item?->unit?->short_name }}</td>
                                <td class="text-end">{{ inv_qty($line->quantity) }}</td>
                                <td class="text-end">{{ inv_qty($line->received_qty) }}</td>
                                <td class="text-end">
                                    <span class="badge p-1 text-white bg-{{ $remaining <= 0 ? 'success' : 'warning' }}">{{ inv_qty($remaining) }}</span>
                                </td>
                                <td class="text-end">{{ number_format($line->rate, 2) }}</td>
                                <td class="text-end">{{ number_format($line->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold"><td colspan="6" class="text-end">Total</td><td class="text-end">{{ number_format($purchaseOrder->total_amount, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>

            <h6>Challans Received Against This PO ({{ $purchaseOrder->grns->count() }})</h6>
            <p class="text-muted" style="font-size:13px;">Every delivery — even a partial one — is received as its own separate challan/GRN, shown one by one below in the order received.</p>

            @if($purchaseOrder->grns->isEmpty())
                <p class="text-muted">No goods have been received against this purchase order yet.</p>
            @else
                @foreach($purchaseOrder->grns as $grn)
                    <div class="card mb-3 border-{{ $loop->first ? 'primary' : 'secondary' }}">
                        <div class="card-header d-flex justify-content-between align-items-center bg-light">
                            <div>
                                <span class="badge p-1 text-white bg-dark me-2">Challan #{{ $loop->iteration }}</span>
                                <strong>{{ $grn->grn_number }}</strong>
                                <span class="text-muted">— Challan/Invoice No: {{ $grn->challan_invoice_no ?? '—' }}</span>
                            </div>
                            <div class="text-end">
                                <span class="badge p-1 text-white bg-info">{{ $grn->receive_date?->format('d M Y') }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-2">
                                    <thead>
                                        <tr><th>Item Code</th><th>Item Name</th><th class="text-end">Received Qty</th><th class="text-end">Rejected Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th>Lot/Batch</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($grn->items as $gi)
                                            <tr>
                                                <td>{{ $gi->item?->item_code }}</td>
                                                <td>{{ $gi->item?->item_name }}</td>
                                                <td class="text-end">{{ inv_qty($gi->received_qty) }}</td>
                                                <td class="text-end">{{ inv_qty($gi->rejected_qty) }}</td>
                                                <td class="text-end">{{ number_format($gi->rate, 2) }}</td>
                                                <td class="text-end">{{ number_format($gi->amount, 2) }}</td>
                                                <td>{{ collect([$gi->lot_no, $gi->batch_no])->filter()->implode(' / ') ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold"><td colspan="5" class="text-end">Challan Total</td><td class="text-end">{{ number_format($grn->total_amount, 2) }}</td><td></td></tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="text-muted" style="font-size:12px;">Received by: {{ $grn->receiver?->name ?? '—' }}</div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
