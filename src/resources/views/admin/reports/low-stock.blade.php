@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Low Stock Report') }}
    @else
        <title>{{ websiteTitle('Low Stock Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Low Stock Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Low Stock Report</h5>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'low-stock'])
            @endunless
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead><tr><th>Item Code</th><th>Item Name</th><th>Category</th><th class="text-end">Current Stock</th><th class="text-end">Minimum Stock</th></tr></thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr class="table-warning">
                                <td>{{ $item->item_code }}</td>
                                <td>{{ $item->item_name }}</td>
                                <td>{{ $item->category?->name }}</td>
                                <td class="text-end">{{ number_format($item->current_stock, 4) }} {{ $item->unit?->short_name }}</td>
                                <td class="text-end">{{ number_format($item->minimum_stock, 4) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No items are below minimum stock.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
