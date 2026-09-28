@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Store Wise Stock Report') }}
    @else
        <title>{{ websiteTitle('Store Wise Stock Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Store Wise Stock Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Store Wise Stock</h4>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'store-wise-stock'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row mb-3 align-items-end">
                    <div class="col-md-4 mb-2">
                        <select name="item_id" class="form-control form-control-sm inv-select2">
                            <option value="">All Items</option>
                            @foreach($filterItems as $filterItem)
                                <option value="{{ $filterItem->id }}" @selected(request('item_id') == $filterItem->id)>{{ $filterItem->item_code }} — {{ $filterItem->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end flex-wrap gap-1">
                        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                        <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Reset</a>
                    </div>
                </form>
            @endunless
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead><tr><th>Store</th><th class="text-right">Distinct Items</th>@if(request('item_id'))<th class="text-right">Stock Qty</th>@endif<th class="text-right">Total Value</th></tr></thead>
                    <tbody>
                        @forelse($stores as $row)
                            <tr>
                                <td>{{ $row->store->name }}</td>
                                <td class="text-right">{{ $row->items_count }}</td>@if(request('item_id'))<td class="text-right">{{ inv_qty($row->total_qty) }}</td>@endif
                                <td class="text-right">{{ inv_qty($row->total_value) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No stores found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($stores->isNotEmpty())
                        <tfoot>
                            <tr class="font-weight-bold"><td class="text-right">Total</td><td class="text-right"></td>@if(request('item_id'))<td class="text-right">{{ inv_qty($stores->sum('total_qty')) }}</td>@endif<td class="text-right">{{ inv_qty($stores->sum('total_value')) }}</td></tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
