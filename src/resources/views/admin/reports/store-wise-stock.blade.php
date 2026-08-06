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
            <h5 class="mb-0">Store Wise Stock</h5>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'store-wise-stock'])
            @endunless
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead><tr><th>Store</th><th class="text-end">Distinct Items</th><th class="text-end">Total Value</th></tr></thead>
                    <tbody>
                        @forelse($stores as $row)
                            <tr>
                                <td>{{ $row->store->name }}</td>
                                <td class="text-end">{{ $row->items_count }}</td>
                                <td class="text-end">{{ number_format($row->total_value, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No stores found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
