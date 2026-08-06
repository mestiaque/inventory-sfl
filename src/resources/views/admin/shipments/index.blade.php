@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Shipments') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Shipments</h5>
            @can('inv_shipment.add')
                <a href="{{ route('inventory.shipments.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Shipment</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search shipment no" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="buyer_id" class="form-control inv-select2">
                        <option value="">All Buyers</option>
                        @foreach($buyers as $buyer)
                            <option value="{{ $buyer->id }}" @selected(request('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control inv-select2">
                        <option value="">All Status</option>
                        @foreach(['pending' => 'Pending', 'dispatched' => 'Dispatched', 'delivered' => 'Delivered'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('inventory.shipments.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Shipment No</th><th>Buyer</th><th>Invoice No</th><th>Gate Pass</th><th>Date</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($shipments as $shipment)
                            <tr>
                                <td>{{ $loop->iteration + $shipments->firstItem() - 1 }}</td>
                                <td>{{ $shipment->shipment_no }}</td>
                                <td>{{ $shipment->buyer?->name }}</td>
                                <td>{{ $shipment->invoice_no }}</td>
                                <td>{{ $shipment->gatePasses->pluck('gate_pass_no')->implode(', ') ?: ($shipment->gatePass?->gate_pass_no ?? '—') }}</td>
                                <td>{{ $shipment->shipment_date?->format('d M Y') }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'dispatched' => 'warning', 'delivered' => 'success'][$shipment->status] ?? 'secondary' }}">
                                        {{ ucfirst($shipment->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#viewShpModal{{ $shipment->id }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @can('inv_gate_pass.add')
                                        @if($shipment->gatePasses->isEmpty())
                                            <a href="{{ route('inventory.gate-passes.create', ['shipment_id' => $shipment->id]) }}" class="btn btn-sm btn-outline-success">
                                                <i class="fa-solid fa-door-open"></i> Gate Pass
                                            </a>
                                        @endif
                                    @endcan
                                    @can('inv_shipment.edit')
                                        @if($shipment->status !== 'delivered')
                                            <form method="POST" action="{{ route('inventory.shipments.status', $shipment) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $shipment->status === 'pending' ? 'dispatched' : 'delivered' }}">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    Mark {{ $shipment->status === 'pending' ? 'Dispatched' : 'Delivered' }}
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No shipments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $shipments->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- View modals live outside the table (a <div> can't legally be a direct child of <tbody>, and a nested <table> inside it would get corrupted by the browser's table-repair parsing otherwise). --}}
@foreach($shipments as $shipment)
    <div class="modal fade" id="viewShpModal{{ $shipment->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Shipment Details — {{ $shipment->shipment_no }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-3">Buyer</dt><dd class="col-sm-9">{{ $shipment->buyer?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Shipment Date</dt><dd class="col-sm-9">{{ $shipment->shipment_date?->format('d M Y') }}</dd>
                        <dt class="col-sm-3">Invoice No</dt><dd class="col-sm-9">{{ $shipment->invoice_no ?: '—' }}</dd>
                        <dt class="col-sm-3">Packing List No</dt><dd class="col-sm-9">{{ $shipment->packing_list_no ?: '—' }}</dd>
                        <dt class="col-sm-3">Gate Pass</dt><dd class="col-sm-9">{{ $shipment->gatePasses->pluck('gate_pass_no')->implode(', ') ?: ($shipment->gatePass?->gate_pass_no ?? '— not issued yet') }}</dd>
                        <dt class="col-sm-3">Store</dt><dd class="col-sm-9">{{ $shipment->store?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'dispatched' => 'warning', 'delivered' => 'success'][$shipment->status] ?? 'secondary' }}">
                                {{ ucfirst($shipment->status) }}
                            </span>
                        </dd>
                        <dt class="col-sm-3">Created By</dt><dd class="col-sm-9">{{ $shipment->creator?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Remarks</dt><dd class="col-sm-9">{{ $shipment->remarks ?: '—' }}</dd>
                    </dl>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead>
                                <tr><th>#</th><th>Item</th><th>Unit</th><th class="text-end">Quantity</th></tr>
                            </thead>
                            <tbody>
                                @foreach($shipment->items as $line)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $line->item?->item_code }} — {{ $line->item?->item_name }}</td>
                                        <td>{{ $line->item?->unit?->short_name ?? '—' }}</td>
                                        <td class="text-end">{{ inv_qty($line->quantity) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@include('sfl-inventory::admin.partials.select2-init')
@endsection
