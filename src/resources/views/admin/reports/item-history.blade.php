@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Item History Report') }}
    @else
        <title>{{ websiteTitle('Item History Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Item History Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Item History{{ $selectedItem ? ' — ' . $selectedItem->item_code . ' ' . $selectedItem->item_name : '' }}</h5>
            @unless($printMode)
                @if($selectedItem)
                    @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'item-history'])
                @endif
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <select name="item_id" class="form-control inv-select2" required>
                            <option value="">— Select an item —</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-secondary">Show</button>
                    </div>
                </form>
            @endunless

            @if($selectedItem)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm align-middle">
                        <thead><tr><th>Date</th><th>Store</th><th>Type</th><th class="text-end">Qty In</th><th class="text-end">Qty Out</th><th class="text-end">Rate</th><th class="text-end">Value</th></tr></thead>
                        <tbody>
                            @forelse($transactions as $txn)
                                <tr>
                                    <td>{{ $txn->transaction_date?->format('d M Y') }}</td>
                                    <td>{{ $txn->store?->name }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $txn->transaction_type)) }}</td>
                                    <td class="text-end text-success">{{ $txn->qty_in > 0 ? number_format($txn->qty_in, 4) : '' }}</td>
                                    <td class="text-end text-danger">{{ $txn->qty_out > 0 ? number_format($txn->qty_out, 4) : '' }}</td>
                                    <td class="text-end">{{ number_format($txn->rate, 2) }}</td>
                                    <td class="text-end">{{ number_format($txn->value, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No movements found for this item.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">Select an item to view its full movement history.</p>
            @endif
        </div>
    </div>
</div>

@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
