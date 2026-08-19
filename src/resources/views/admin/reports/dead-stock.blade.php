@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Dead Stock Report') }}
    @else
        <title>{{ websiteTitle('Dead Stock Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Dead Stock Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Dead Stock Report</h5>
                <small class="text-muted">Items with stock on hand but no outbound movement in the last {{ config('sfl-inventory.dead_stock_days', 90) }} days.</small>
            </div>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'dead-stock'])
            @endunless
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead><tr><th>Item Code</th><th>Item Name</th><th>Category</th><th class="text-end">Current Stock</th><th class="text-end">Stock Value</th></tr></thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr class="table-danger">
                                <td>{{ $item->item_code }}</td>
                                <td>{{ $item->item_name }}</td>
                                <td>{{ $item->category?->name }}</td>
                                <td class="text-end">{{ number_format($item->current_stock, 2) }} {{ $item->unit?->short_name }}</td>
                                <td class="text-end">{{ number_format($item->stock_value, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No dead stock found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
