@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('GRN ' . $grn->grn_number) }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">GRN {{ $grn->grn_number }}</h5>
            <div class="d-flex align-items-center gap-2">
                @can('inv_grn.edit')
                    <a href="{{ route('inventory.grns.edit', $grn) }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-pen"></i> Edit</a>
                @endcan
                <a href="{{ route('inventory.grns.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row mb-4">
                <dt class="col-sm-2">Source</dt>
                <dd class="col-sm-4">
                    @if($grn->source_type === 'buyer_supplied')
                        <span class="badge p-1 text-white bg-info">Buyer Supplied</span>
                    @else
                        <span class="badge p-1 text-white bg-secondary">Purchase</span>
                    @endif
                </dd>
                <dt class="col-sm-2">Status</dt>
                <dd class="col-sm-4"><span class="badge p-1 text-white bg-success">{{ ucfirst($grn->status) }}</span></dd>

                <dt class="col-sm-2">PO Number</dt>
                <dd class="col-sm-4">{{ $grn->purchaseOrder?->po_number ?? '—' }}</dd>
                <dt class="col-sm-2">Store</dt>
                <dd class="col-sm-4">{{ $grn->store?->name }}</dd>

                <dt class="col-sm-2">{{ $grn->source_type === 'buyer_supplied' ? 'Buyer' : 'Supplier' }}</dt>
                <dd class="col-sm-4">{{ $grn->source_type === 'buyer_supplied' ? $grn->buyer?->name : $grn->supplier?->name }}</dd>
                <dt class="col-sm-2">Receive Date</dt>
                <dd class="col-sm-4">{{ $grn->receive_date?->format('d M Y') }}</dd>

                <dt class="col-sm-2">Style</dt>
                <dd class="col-sm-4">{{ $grn->style ?? '—' }}</dd>
                <dt class="col-sm-2">Order Ref</dt>
                <dd class="col-sm-4">{{ $grn->order_ref ?? '—' }}</dd>

                <dt class="col-sm-2">Invoice / Challan No.</dt>
                <dd class="col-sm-4">{{ $grn->challan_invoice_no ?? '—' }}</dd>
                <dt class="col-sm-2">Received By</dt>
                <dd class="col-sm-4">{{ $grn->receiver?->name ?? '—' }}</dd>

                <dt class="col-sm-2">Created By</dt>
                <dd class="col-sm-4">{{ $grn->creator?->name ?? '—' }}</dd>
                <dt class="col-sm-2">Created At</dt>
                <dd class="col-sm-4">{{ $grn->created_at?->format('d M Y, h:i A') }}</dd>

                @if($grn->remarks)
                    <dt class="col-sm-2">Remarks</dt>
                    <dd class="col-sm-10">{{ $grn->remarks }}</dd>
                @endif
            </dl>

            <h6>Items</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Item</th><th>Unit</th><th class="text-end">Received Qty</th><th class="text-end">Rejected Qty</th>
                            <th class="text-end">Rate</th><th class="text-end">Amount</th><th>Lot No</th><th>Batch No</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grn->items as $item)
                            <tr>
                                <td>{{ $item->item?->item_code }} — {{ $item->item?->item_name }}</td>
                                <td>{{ $item->item?->unit?->short_name }}</td>
                                <td class="text-end">{{ inv_qty($item->received_qty) }}</td>
                                <td class="text-end">{{ inv_qty($item->rejected_qty) }}</td>
                                <td class="text-end">{{ number_format($item->rate, 2) }}</td>
                                <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                                <td>{{ $item->lot_no ?? '—' }}</td>
                                <td>{{ $item->batch_no ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold"><td colspan="5" class="text-end">Total</td><td class="text-end">{{ number_format($grn->total_amount, 2) }}</td><td colspan="2"></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
