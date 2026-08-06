@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('GRN Report') }}
    @else
        <title>{{ websiteTitle('GRN Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'GRN Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">GRN Report</h5>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'grn'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <select name="store_id" class="form-control inv-select2">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="supplier_id" class="form-control inv-select2">
                            <option value="">All Suppliers</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From"></div>
                    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-secondary">Filter</button></div>
                </form>
            @endunless

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead><tr><th>GRN No</th><th>PO No</th><th>Store</th><th>Supplier</th><th>Date</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @forelse($grns as $grn)
                            <tr>
                                <td>{{ $grn->grn_number }}</td>
                                <td>{{ $grn->purchaseOrder?->po_number ?? '—' }}</td>
                                <td>{{ $grn->store?->name }}</td>
                                <td>{{ $grn->supplier?->name }}</td>
                                <td>{{ $grn->receive_date?->format('d M Y') }}</td>
                                <td class="text-end">{{ number_format($grn->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">No GRNs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @unless($printMode)
                {{ $grns->links('pagination::bootstrap-5') }}
            @endunless
        </div>
    </div>
</div>
@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
