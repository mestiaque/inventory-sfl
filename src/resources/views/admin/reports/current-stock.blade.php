@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Current Stock Report') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Current Stock Report</h5></div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select name="category_id" class="form-control inv-select2">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="{{ route('inventory.reports.current-stock') }}" class="btn btn-light">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead><tr><th>Item Code</th><th>Item Name</th><th>Category</th><th>Unit</th><th class="text-end">Current Stock (All Stores)</th><th class="text-end">Stock Value</th></tr></thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->item_code }}</td>
                                <td>{{ $item->item_name }}</td>
                                <td>{{ $item->category?->name }}</td>
                                <td>{{ $item->unit?->short_name }}</td>
                                <td class="text-end">{{ number_format($item->current_stock, 4) }}</td>
                                <td class="text-end">{{ number_format($item->stock_value, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">No stock records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@include('sfl-inventory::admin.partials.select2-init')
@endsection
