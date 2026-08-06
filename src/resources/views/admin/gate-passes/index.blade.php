@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Gate Pass') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Gate Pass</h5>
            @can('inv_gate_pass.add')
                <a href="{{ route('inventory.gate-passes.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Gate Pass</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search gate pass no" value="{{ request('search') }}">
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
                    <select name="store_id" class="form-control inv-select2">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control inv-select2">
                        <option value="">All Status</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="issued" @selected(request('status') === 'issued')>Issued</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-2 mt-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-2 mt-2">
                    <a href="{{ route('inventory.gate-passes.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Gate Pass No</th><th>Shipment</th><th>Buyer</th><th>Vehicle</th><th>Store</th><th>Date</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($gatePasses as $gatePass)
                            <tr>
                                <td>{{ $loop->iteration + $gatePasses->firstItem() - 1 }}</td>
                                <td>{{ $gatePass->gate_pass_no }}</td>
                                <td>{{ $gatePass->shipment?->shipment_no ?? '— (direct)' }}</td>
                                <td>{{ $gatePass->buyer?->name }}</td>
                                <td>{{ $gatePass->vehicle_no }}</td>
                                <td>{{ $gatePass->store?->name }}</td>
                                <td>{{ $gatePass->gate_pass_date?->format('d M Y') }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'issued' => 'success', 'cancelled' => 'danger'][$gatePass->status] ?? 'secondary' }}">
                                        {{ ucfirst($gatePass->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#viewGpModal{{ $gatePass->id }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @can('inv_gate_pass.approve')
                                        @if($gatePass->status === 'pending')
                                            <form method="POST" action="{{ route('inventory.gate-passes.approve', $gatePass) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Issue this gate pass and release goods?')">Issue</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No gate passes found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $gatePasses->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- View modals live outside the table (a <div> can't legally be a direct child of <tbody>, and a nested <table> inside it would get corrupted by the browser's table-repair parsing otherwise). --}}
@foreach($gatePasses as $gatePass)
    <div class="modal fade" id="viewGpModal{{ $gatePass->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gate Pass Details — {{ $gatePass->gate_pass_no }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-3">Shipment</dt><dd class="col-sm-9">{{ $gatePass->shipment?->shipment_no ?? '— (direct, no shipment)' }}</dd>
                        <dt class="col-sm-3">Store</dt><dd class="col-sm-9">{{ $gatePass->store?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Gate Pass Date</dt><dd class="col-sm-9">{{ $gatePass->gate_pass_date?->format('d M Y') }}</dd>
                        <dt class="col-sm-3">Buyer</dt><dd class="col-sm-9">{{ $gatePass->buyer?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Vehicle No</dt><dd class="col-sm-9">{{ $gatePass->vehicle_no ?: '—' }}</dd>
                        <dt class="col-sm-3">Driver</dt><dd class="col-sm-9">{{ collect([$gatePass->driver_name, $gatePass->driver_contact])->filter()->implode(' / ') ?: '—' }}</dd>
                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            <span class="badge p-1 text-white bg-{{ ['pending' => 'secondary', 'issued' => 'success', 'cancelled' => 'danger'][$gatePass->status] ?? 'secondary' }}">
                                {{ ucfirst($gatePass->status) }}
                            </span>
                        </dd>
                        <dt class="col-sm-3">Created By</dt><dd class="col-sm-9">{{ $gatePass->creator?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Remarks</dt><dd class="col-sm-9">{{ $gatePass->remarks ?: '—' }}</dd>
                    </dl>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead>
                                <tr><th>#</th><th>Item</th><th>Unit</th><th class="text-end">Quantity</th></tr>
                            </thead>
                            <tbody>
                                @foreach($gatePass->items as $line)
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
