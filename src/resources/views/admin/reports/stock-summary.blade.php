@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Stock Summary Report') }}
    @else
        <title>{{ websiteTitle('Stock Summary Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Stock Summary Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Stock Summary by Category</h5>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'stock-summary'])
            @endunless
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead><tr><th>Category</th><th class="text-end">Items</th><th class="text-end">Total Qty</th><th class="text-end">Total Value</th></tr></thead>
                    <tbody>
                        @forelse($summary as $row)
                            <tr>
                                <td>{{ $row->category->name }}</td>
                                <td class="text-end">{{ $row->items_count }}</td>
                                <td class="text-end">{{ inv_qty($row->total_qty) }}</td>
                                <td class="text-end">{{ inv_qty($row->total_value) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No stock records found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($summary->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold"><td colspan="3" class="text-end">Grand Total</td><td class="text-end">{{ inv_qty($summary->sum('total_value')) }}</td></tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
