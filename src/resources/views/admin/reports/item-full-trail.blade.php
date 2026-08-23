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
                <small class="text-muted">Every Purchase Order, GRN, Requisition and Issue that ever touched this item — who did it, and the document number.</small>
            </div>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'item-full-trail'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-6">
                        <select name="item_id" class="form-control inv-select2" required>
                            <option value="">— Select an item —</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-secondary w-100">Show</button>
                    </div>
                </form>
            @endunless

            @if(! $selectedItem)
                <div class="text-center text-muted py-4">Select an item above to see its full purchase → receive → requisition → issue trail.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Document Type</th>
                                <th>Document No</th>
                                <th class="text-end">Qty</th>
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
                                        <span class="badge bg-{{ ['Purchase Order' => 'primary', 'GRN' => 'success', 'Requisition' => 'warning', 'Issue' => 'danger'][$row->document_type] ?? 'secondary' }}">
                                            {{ $row->document_type }}
                                        </span>
                                    </td>
                                    <td>{{ $row->document_no }}</td>
                                    <td class="text-end">{{ inv_qty($row->qty) }}</td>
                                    <td>{{ $row->person_name ?? '—' }}</td>
                                    <td>{{ $row->party_name ?? '—' }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $row->status ?? '')) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No purchase/receive/requisition/issue history found for this item.</td></tr>
                            @endforelse
                        </tbody>
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
