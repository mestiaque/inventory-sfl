@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Shipment Report') }}
    @else
        <title>{{ websiteTitle('Shipment Report') }}</title>
    @endif
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Shipment Report'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Shipment Report</h5>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'shipment'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <select name="buyer_id" class="form-control inv-select2">
                            <option value="">All Buyers</option>
                            @foreach($buyers as $buyer)
                                <option value="{{ $buyer->id }}" @selected(request('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control inv-select2">
                            <option value="">All Status</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                            <option value="dispatched" @selected(request('status') === 'dispatched')>Dispatched</option>
                            <option value="delivered" @selected(request('status') === 'delivered')>Delivered</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From"></div>
                    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-secondary">Filter</button></div>
                </form>
            @endunless

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead><tr><th>Shipment No</th><th>Buyer</th><th>Invoice No</th><th>Gate Pass</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($shipments as $shipment)
                            <tr>
                                <td>{{ $shipment->shipment_no }}</td>
                                <td>{{ $shipment->buyer?->name }}</td>
                                <td>{{ $shipment->invoice_no }}</td>
                                <td>{{ $shipment->gatePasses->pluck('gate_pass_no')->implode(', ') ?: ($shipment->gatePass?->gate_pass_no ?? '—') }}</td>
                                <td>{{ $shipment->shipment_date?->format('d M Y') }}</td>
                                <td>{{ ucfirst($shipment->status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">No shipments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @unless($printMode)
                {{ $shipments->links('pagination::bootstrap-5') }}
            @endunless
        </div>
    </div>
</div>
@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
