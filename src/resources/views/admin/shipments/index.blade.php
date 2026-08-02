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
                                <td>{{ $shipment->gatePass?->gate_pass_no ?? '—' }}</td>
                                <td>{{ $shipment->shipment_date?->format('d M Y') }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'dispatched' => 'warning', 'delivered' => 'success'][$shipment->status] ?? 'secondary' }}">
                                        {{ ucfirst($shipment->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
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
@include('sfl-inventory::admin.partials.select2-init')
@endsection
