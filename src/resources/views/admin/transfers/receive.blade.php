@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Receive Transfer') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Receive Transfer — {{ $transfer->transfer_no }}</h5>
            <a href="{{ route('inventory.transfers.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <p><strong>From:</strong> {{ $transfer->fromStore?->name }} &nbsp;→&nbsp; <strong>To:</strong> {{ $transfer->toStore?->name }}</p>

            <form method="POST" action="{{ route('inventory.transfers.receive', $transfer) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th>Item</th><th>Sent Qty</th><th style="width:180px">Received Qty</th></tr></thead>
                        <tbody>
                            @foreach($transfer->items as $item)
                                <tr>
                                    <td>{{ $item->item?->item_code }} — {{ $item->item?->item_name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                        <input type="number" step="0.0001" min="0" max="{{ $item->quantity }}" class="form-control"
                                            name="items[{{ $loop->index }}][received_qty]" value="{{ $item->quantity }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary">Confirm Receipt &amp; Update Stock</button>
                <a href="{{ route('inventory.transfers.index') }}" class="btn btn-light">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
