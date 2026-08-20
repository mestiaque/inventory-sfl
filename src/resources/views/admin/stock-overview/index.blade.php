@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Main Store Inventory') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Main Store Inventory</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <select name="item_id" class="form-control inv-select2">
                        <option value="">All Items</option>
                        @foreach($allItems as $item)
                            <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="store_id" class="form-control inv-select2">
                        <option value="">All Stores</option>
                        @foreach($allStores as $store)
                            <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="category_id" class="form-control inv-select2">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('inventory.stock-overview.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead>
                        <tr><th>Item</th><th>Category</th><th>Store</th><th class="text-end">Current Stock</th><th class="text-end">Reserved</th><th class="text-end">Available</th><th class="text-end">Value</th></tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php $item = $items->get($row->item_id); $store = $stores->get($row->store_id); @endphp
                            <tr>
                                <td>{{ $item?->item_code }} — {{ $item?->item_name }}</td>
                                <td>{{ $item?->category?->name }}</td>
                                <td>{{ $store?->name }}</td>
                                <td class="text-end">{{ inv_qty($row->current) }} {{ $item?->unit?->short_name }}</td>
                                <td class="text-end">{{ inv_qty($row->reserved) }}</td>
                                <td class="text-end">{{ inv_qty($row->available) }}</td>
                                <td class="text-end">{{ inv_qty($row->value) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No stock records found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="6" class="text-end">Total Stock Value</td>
                                <td class="text-end">{{ inv_qty($rows->sum('value')) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@endsection
