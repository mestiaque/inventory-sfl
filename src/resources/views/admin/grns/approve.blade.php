@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Receive Approval') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Receive Approval — {{ $grn->grn_number }}</h5>
            <a href="{{ route('inventory.grns.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <p>
                <strong>PO Number:</strong> {{ $grn->purchaseOrder?->po_number ?? '—' }} &nbsp;|&nbsp;
                <strong>Store:</strong> {{ $grn->store?->name }} &nbsp;|&nbsp;
                <strong>Supplier:</strong> {{ $grn->supplier?->name ?? '—' }} &nbsp;|&nbsp;
                <strong>Receive Date:</strong> {{ $grn->receive_date?->format('d M Y') }}
                <br><strong>Invoice / Challan No.:</strong> {{ $grn->challan_invoice_no ?? '—' }} &nbsp;|&nbsp;
                <strong>Prepared By:</strong> {{ $grn->creator?->name ?? '—' }}
            </p>

            <form method="POST" action="{{ route('inventory.grns.approval', $grn) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Item</th><th>Unit</th><th>Color</th><th>Size</th><th class="text-end">Received Qty</th>
                                <th class="text-end">Rate</th><th class="text-end">Amount</th><th>Lot No</th><th>Batch No</th><th>Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grn->items as $item)
                                <tr>
                                    <td>{{ $item->item?->item_code }} — {{ $item->item?->item_name }}</td>
                                    <td>{{ $item->item?->unit?->short_name }}</td>
                                    <td>{{ $item->color?->name ?? '—' }}</td>
                                    <td>{{ $item->size?->name ?? '—' }}</td>
                                    <td class="text-end">{{ inv_qty($item->received_qty) }}</td>
                                    <td class="text-end">{{ inv_qty($item->rate) }}</td>
                                    <td class="text-end">{{ inv_qty($item->amount) }}</td>
                                    <td>{{ $item->lot_no ?? '—' }}</td>
                                    <td>{{ $item->batch_no ?? '—' }}</td>
                                    <td>{{ optional($item->expiry_date)->format('d M Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold"><td colspan="6" class="text-end">Total</td><td class="text-end">{{ inv_qty($grn->total_amount) }}</td><td colspan="3"></td></tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="approval_remarks" class="form-control" rows="2"></textarea>
                </div>

                @can('inv_grn.approve')
                    <button type="submit" name="decision" value="approve" class="btn btn-success">Approve</button>
                @endcan
                @can('inv_grn.reject')
                    <button type="submit" name="decision" value="reject" class="btn btn-danger" onclick="return confirm('Reject this GRN? The claimed quantity on its Purchase Order will be released.')">Reject</button>
                @endcan
                <a href="{{ route('inventory.grns.index') }}" class="btn btn-light">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
