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
        <p class="text-center mb-2">Period: {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Item History{{ $selectedItem ? ' — ' . $selectedItem->item_code . ' ' . $selectedItem->item_name : ' — All Items' }}</h5>
                @if($selectedItem)
                    <span class="badge badge-info mt-1">Current Stock: {{ inv_qty($currentStock) }} {{ $selectedItem->unit?->short_name }}</span>
                @endif
            </div>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'item-history'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row mb-3 align-items-end">
                    <div class="col-md-3 mb-2">
                        <select name="item_id" class="form-control form-control-sm inv-select2">
                            <option value="">— All Items —</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="color_id" class="form-control form-control-sm inv-select2">
                            <option value="">All Colors</option>
                            @foreach($colors as $color)
                                <option value="{{ $color->id }}" @selected(request('color_id') == $color->id)>{{ $color->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="size_id" class="form-control form-control-sm inv-select2">
                            <option value="">All Sizes</option>
                            @foreach($sizes as $size)
                                <option value="{{ $size->id }}" @selected(request('size_id') == $size->id)>{{ $size->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $from }}" placeholder="From">
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $to }}" placeholder="To">
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end flex-wrap gap-1">
                        <button type="submit" class="btn btn-secondary btn-sm">Show</button>
                        <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Reset</a>
                    </div>
                </form>
            @endunless

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            @unless($selectedItem)
                                <th>Item</th>
                            @endunless
                            <th>Color</th><th>Size</th><th>Store</th><th>Type</th><th class="text-right">Qty In</th><th class="text-right">Qty Out</th><th class="text-right">Rate</th><th class="text-right">Value</th><th class="text-right">Current Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($txn->transaction_date)->format('d M Y') }}</td>
                                @unless($selectedItem)
                                    <td>{{ $txn->item_code }} — {{ $txn->item_name }}</td>
                                @endunless
                                <td>{{ $txn->color_name ?? '—' }}</td>
                                <td>{{ $txn->size_name ?? '—' }}</td>
                                <td>{{ $txn->store_name }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $txn->transaction_type)) }}</td>
                                <td class="text-right text-success">{{ $txn->qty_in > 0 ? inv_qty($txn->qty_in) : '' }}</td>
                                <td class="text-right text-danger">{{ $txn->qty_out > 0 ? inv_qty($txn->qty_out) : '' }}</td>
                                <td class="text-right">{{ inv_qty($txn->rate) }}</td>
                                <td class="text-right">{{ inv_qty($txn->value) }}</td>
                                <td class="text-right font-weight-bold">{{ inv_qty($txn->running_balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $selectedItem ? 10 : 11 }}" class="text-center text-muted">No movements found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @unless($printMode)
                {{ $transactions->links('pagination::bootstrap-5') }}
            @endunless
        </div>
    </div>
</div>

@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
