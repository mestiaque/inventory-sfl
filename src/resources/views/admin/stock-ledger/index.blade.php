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
            <h4 class="mb-0">Stock Ledger</h4>
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <select name="item_id" class="form-control form-control-sm inv-select2">
                        <option value="">All Items</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="color_id" class="form-control form-control-sm inv-select2">
                        <option value="">All Colors</option>
                        @foreach($colors as $color)
                            <option value="{{ $color->id }}" @selected(request('color_id') == $color->id)>{{ $color->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="size_id" class="form-control form-control-sm inv-select2">
                        <option value="">All Sizes</option>
                        @foreach($sizes as $size)
                            <option value="{{ $size->id }}" @selected(request('size_id') == $size->id)>{{ $size->name }}</option>
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
                <div class="col-md-3 mb-2">
                    <select name="transaction_type" class="form-control form-control-sm inv-select2">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" @selected(request('transaction_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-3 mb-2 d-flex align-items-end flex-wrap gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                    <a href="{{ route('inventory.stock-ledger.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>Date</th><th>Item</th><th>Color</th><th>Size</th><th>Store</th><th>Type</th><th class="text-right">Qty In</th><th class="text-right">Qty Out</th><th class="text-right">Rate</th><th class="text-right">Value</th><th>Remarks</th></tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                            <tr>
                                <td>{{ $txn->transaction_date?->format('d M Y') }}</td>
                                <td>{{ $txn->item?->item_code }} — {{ $txn->item?->item_name }}</td>
                                <td>{{ $txn->color?->name ?? '—' }}</td>
                                <td>{{ $txn->size?->name ?? '—' }}</td>
                                <td>{{ $txn->store?->name }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $txn->transaction_type)) }}</td>
                                <td class="text-right text-success">{{ $txn->qty_in > 0 ? inv_qty($txn->qty_in) : '' }}</td>
                                <td class="text-right text-danger">{{ $txn->qty_out > 0 ? inv_qty($txn->qty_out) : '' }}</td>
                                <td class="text-right">{{ inv_qty($txn->rate) }}</td>
                                <td class="text-right">{{ inv_qty($txn->value) }}</td>
                                <td>{{ $txn->remarks }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-muted">No ledger entries found.</td></tr>
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
