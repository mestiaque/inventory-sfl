@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Stock Ledger') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Stock Ledger</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
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
                    <select name="transaction_type" class="form-control inv-select2">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" @selected(request('transaction_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('inventory.stock-ledger.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead>
                        <tr><th>Date</th><th>Item</th><th>Store</th><th>Type</th><th class="text-end">Qty In</th><th class="text-end">Qty Out</th><th class="text-end">Rate</th><th class="text-end">Value</th><th>Remarks</th></tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                            <tr>
                                <td>{{ $txn->transaction_date?->format('d M Y') }}</td>
                                <td>{{ $txn->item?->item_code }} — {{ $txn->item?->item_name }}</td>
                                <td>{{ $txn->store?->name }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $txn->transaction_type)) }}</td>
                                <td class="text-end text-success">{{ $txn->qty_in > 0 ? inv_qty($txn->qty_in) : '' }}</td>
                                <td class="text-end text-danger">{{ $txn->qty_out > 0 ? inv_qty($txn->qty_out) : '' }}</td>
                                <td class="text-end">{{ inv_qty($txn->rate) }}</td>
                                <td class="text-end">{{ inv_qty($txn->value) }}</td>
                                <td>{{ $txn->remarks }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No ledger entries found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $transactions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@endsection
