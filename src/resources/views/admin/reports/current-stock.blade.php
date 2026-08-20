@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Current Stock Report') }}
    @else
        <title>{{ websiteTitle('Current Stock Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Current Stock Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Current Stock Report</h5>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'current-stock'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <input type="text" name="item_code" class="form-control" placeholder="Item Code" value="{{ request('item_code') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="item_name" class="form-control" placeholder="Item Name" value="{{ request('item_name') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="category_id" class="form-control inv-select2">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="unit_id" class="form-control inv-select2">
                            <option value="">All Units</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-secondary">Filter</button>
                        <a href="{{ route('inventory.reports.current-stock') }}" class="btn btn-light">Reset</a>
                    </div>
                </form>
            @endunless

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
                                <td class="text-end">{{ inv_qty($item->current_stock) }}</td>
                                <td class="text-end">{{ inv_qty($item->stock_value) }}</td>
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
@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
