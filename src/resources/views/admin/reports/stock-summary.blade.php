@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Stock Summary Report') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Stock Summary by Category</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead><tr><th>Category</th><th class="text-end">Items</th><th class="text-end">Total Qty</th><th class="text-end">Total Value</th></tr></thead>
                    <tbody>
                        @forelse($summary as $row)
                            <tr>
                                <td>{{ $row->category->name }}</td>
                                <td class="text-end">{{ $row->items_count }}</td>
                                <td class="text-end">{{ number_format($row->total_qty, 4) }}</td>
                                <td class="text-end">{{ number_format($row->total_value, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No stock records found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($summary->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold"><td colspan="3" class="text-end">Grand Total</td><td class="text-end">{{ number_format($summary->sum('total_value'), 2) }}</td></tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
