@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Negative Stock Fix') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Negative Stock Fix</h4>
            @can('inv_negative_stock.fix')
                @if($rows->isNotEmpty())
                    <a href="{{ route('inventory.negative-stock.auto-fix') }}" class="btn btn-danger btn-sm"><i class="fa-solid fa-wand-magic-sparkles"></i> Auto Fix All</a>
                @endif
            @endcan
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Every item/store below has issued more than it ever received. Click <strong>Fix</strong> to see its ledger and
                either move a wrong-store issue to the correct store, or zero the balance with a stock adjustment.
            </p>

            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-4 mb-2">
                    <select name="item_id" class="form-control form-control-sm inv-select2">
                        <option value="">All Items</option>
                        @foreach($allItems as $item)
                            <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <select name="store_id" class="form-control form-control-sm inv-select2">
                        <option value="">All Stores</option>
                        @foreach($allStores as $store)
                            <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2 d-flex align-items-end flex-wrap gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                    <a href="{{ route('inventory.negative-stock.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>Item</th><th>Category</th><th>Color</th><th>Size</th><th>Store</th><th class="text-right">Balance</th><th class="text-center">Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php $item = $items->get($row->item_id); @endphp
                            <tr>
                                <td>{{ $item?->item_code }} — {{ $item?->item_name }}</td>
                                <td>{{ $item?->category?->name }}</td>
                                <td>{{ $colors->get($row->color_id)?->name ?? '—' }}</td>
                                <td>{{ $sizes->get($row->size_id)?->name ?? '—' }}</td>
                                <td>{{ $stores->get($row->store_id)?->name }}</td>
                                <td class="text-right text-danger font-weight-bold">{{ inv_qty($row->balance) }} {{ $item?->unit?->short_name }}</td>
                                <td class="text-center">
                                    <a href="{{ route('inventory.negative-stock.show', array_filter(['item_id' => $row->item_id, 'store_id' => $row->store_id, 'color_id' => $row->color_id, 'size_id' => $row->size_id])) }}" class="btn btn-danger btn-sm">Fix</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-success">No negative stock. Everything is balanced.</td></tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                            <tr class="font-weight-bold"><td colspan="7">Total: {{ $rows->count() }} negative balance(s)</td></tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@endsection
