@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Item Full Trail Report') }}
    @else
        <title>{{ websiteTitle('Item Full Trail Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Item Full Trail Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Item Full Trail{{ $selectedItem ? ' — ' . $selectedItem->item_code . ' ' . $selectedItem->item_name : '' }}</h5>
                <small class="text-muted">Every Purchase Order, Requisition and stock movement (GRN, Issue, reversal, adjustment…) for this item — who did it, the document number, and the running Current Qty after each one.</small>
            </div>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'item-full-trail'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row mb-3 align-items-end">
                    <div class="col-md-3 mb-2">
                        <select name="item_id" class="form-control form-control-sm inv-select2" required>
                            <option value="">— Select an item —</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end flex-wrap gap-1">
                        <button type="submit" class="btn btn-secondary btn-sm">Show</button>
                        <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Reset</a>
                    </div>
                </form>
            @endunless

            @if(! $selectedItem)
                <div class="text-center text-muted py-4">Select an item above to see its full purchase → receive → requisition → issue trail.</div>
            @else
                @php $unit = $selectedItem->unit?->short_name; @endphp
                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted small">Current Stock (all stores)</div>
                            <div class="font-weight-bold {{ $stockByStore->sum('qty') < 0 ? 'text-danger' : '' }}" style="font-size:1.3rem">{{ inv_qty($stockByStore->sum('qty')) }} {{ $unit }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted small">Stock Value (Tk)</div>
                            <div class="font-weight-bold" style="font-size:1.3rem">{{ number_format($stockByStore->sum('value'), 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <table class="table table-bordered table-sm mb-0">
                            <thead><tr><th>Store</th><th class="text-right">Current Stock</th><th class="text-right">Rate</th><th class="text-right">Stock Value</th></tr></thead>
                            <tbody>
                                @forelse($stockByStore as $row)
                                    <tr>
                                        <td>{{ $row->store }}</td>
                                        <td class="text-right {{ $row->qty < 0 ? 'text-danger' : '' }}">{{ inv_qty($row->qty) }} {{ $unit }}</td>
                                        <td class="text-right">{{ number_format($row->rate, 2) }}</td>
                                        <td class="text-right">{{ number_format($row->value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">No stock movement yet.</td></tr>
                                @endforelse
                            </tbody>
                            @if($stockByStore->count() > 1)
                                <tfoot>
                                    <tr class="font-weight-bold"><td class="text-right">Total</td><td class="text-right">{{ inv_qty($stockByStore->sum('qty')) }} {{ $unit }}</td><td></td><td class="text-right">{{ number_format($stockByStore->sum('value'), 2) }}</td></tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Document Type</th>
                                <th>Document No</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Current Qty</th>
                                <th>Color</th>
                                <th>Size</th>
                                <th>Done By</th>
                                <th>Store/Department/Supplier</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row->txn_date)->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge badge-{{ ['Purchase Order' => 'primary', 'GRN' => 'success', 'Requisition' => 'warning', 'Issue' => 'danger', 'Opening' => 'info', 'Adjustment' => 'dark', 'GRN Reversal' => 'secondary', 'Issue Reversal' => 'secondary'][$row->document_type] ?? 'secondary' }}">
                                            {{ $row->document_type }}
                                        </span>
                                    </td>
                                    <td>{{ $row->document_no }}</td>
                                    @if($row->stock_effect === null)
                                        <td class="text-right text-muted" title="No stock movement — {{ $row->document_type }}">{{ inv_qty($row->qty) }}</td>
                                    @else
                                        <td class="text-right {{ $row->stock_effect < 0 ? 'text-danger' : 'text-success' }}">{{ $row->stock_effect < 0 ? '−' : '+' }}{{ inv_qty(abs($row->stock_effect)) }}</td>
                                    @endif
                                    <td class="text-right font-weight-bold {{ $row->current_qty < 0 ? 'text-danger' : '' }}">{{ inv_qty($row->current_qty) }}</td>
                                    <td>{{ $row->color_name ?? '—' }}</td>
                                    <td>{{ $row->size_name ?? '—' }}</td>
                                    <td>{{ $row->person_name ?? '—' }}</td>
                                    <td>{{ $row->party_name ?? '—' }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $row->status ?? '')) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-muted">No purchase/receive/requisition/issue history found for this item.</td></tr>
                            @endforelse
                        </tbody>
                        @if($rows->isNotEmpty())
                            <tfoot>
                                @foreach($rows->groupBy('document_type') as $type => $docs)
                                    <tr class="font-weight-bold"><td colspan="3" class="text-right">Total {{ $type }} ({{ $docs->count() }})</td><td class="text-right">{{ inv_qty($docs->sum('qty')) }}</td><td colspan="6"></td></tr>
                                @endforeach
                                <tr class="font-weight-bold table-active">
                                    <td colspan="3" class="text-right">Stock In − Out = Current Stock</td>
                                    <td class="text-right">{{ inv_qty($rows->where('stock_effect', '>', 0)->sum('stock_effect')) }} − {{ inv_qty(abs($rows->where('stock_effect', '<', 0)->sum('stock_effect'))) }}</td>
                                    <td class="text-right {{ $rows->last()->current_qty < 0 ? 'text-danger' : '' }}">{{ inv_qty($rows->last()->current_qty) }}</td>
                                    <td colspan="5"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
