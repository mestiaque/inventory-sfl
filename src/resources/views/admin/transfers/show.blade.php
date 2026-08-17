@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Transfer ' . $transfer->transfer_no) }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Transfer {{ $transfer->transfer_no }}</h5>
            <div class="d-flex align-items-center gap-2">
                @if($transfer->status === 'pending')
                    @can('inv_transfer.edit')
                        <a href="{{ route('inventory.transfers.edit', $transfer) }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-pen"></i> Edit</a>
                    @endcan
                @endif
                <a href="{{ route('inventory.transfers.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row mb-4">
                <dt class="col-sm-2">From Store</dt>
                <dd class="col-sm-4">{{ $transfer->fromStore?->name }}</dd>
                <dt class="col-sm-2">To Store</dt>
                <dd class="col-sm-4">{{ $transfer->toStore?->name }}</dd>

                <dt class="col-sm-2">Transfer Date</dt>
                <dd class="col-sm-4">{{ $transfer->transfer_date?->format('d M Y') }}</dd>
                <dt class="col-sm-2">Status</dt>
                <dd class="col-sm-4">
                    <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'approved' => 'info', 'in_transit' => 'warning', 'received' => 'success', 'rejected' => 'danger'][$transfer->status] ?? 'secondary' }}">
                        {{ ucwords(str_replace('_', ' ', $transfer->status)) }}
                    </span>
                </dd>

                <dt class="col-sm-2">Requested By</dt>
                <dd class="col-sm-4">{{ $transfer->requester?->name ?? '—' }}</dd>
                <dt class="col-sm-2">Approved By</dt>
                <dd class="col-sm-4">{{ $transfer->approver?->name ?? '—' }}{{ $transfer->approved_at ? ' @ ' . $transfer->approved_at->format('d M Y, h:i A') : '' }}</dd>

                <dt class="col-sm-2">Received By</dt>
                <dd class="col-sm-4">{{ $transfer->receiver?->name ?? '—' }}{{ $transfer->received_at ? ' @ ' . $transfer->received_at->format('d M Y, h:i A') : '' }}</dd>
                <dt class="col-sm-2">Created At</dt>
                <dd class="col-sm-4">{{ $transfer->created_at?->format('d M Y, h:i A') }}</dd>

                @if($transfer->remarks)
                    <dt class="col-sm-2">Remarks</dt>
                    <dd class="col-sm-10">{{ $transfer->remarks }}</dd>
                @endif
            </dl>

            <h6>Items</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>Item</th><th>Unit</th><th class="text-end">Quantity</th><th class="text-end">Received Qty</th></tr>
                    </thead>
                    <tbody>
                        @foreach($transfer->items as $item)
                            <tr>
                                <td>{{ $item->item?->item_code }} — {{ $item->item?->item_name }}</td>
                                <td>{{ $item->item?->unit?->short_name }}</td>
                                <td class="text-end">{{ inv_qty($item->quantity) }}</td>
                                <td class="text-end">{{ inv_qty($item->received_qty) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
