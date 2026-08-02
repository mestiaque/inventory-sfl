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
                        <tr><th>#</th><th>Gate Pass No</th><th>Buyer</th><th>Vehicle</th><th>Store</th><th>Date</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($gatePasses as $gatePass)
                            <tr>
                                <td>{{ $loop->iteration + $gatePasses->firstItem() - 1 }}</td>
                                <td>{{ $gatePass->gate_pass_no }}</td>
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
                                    @can('inv_gate_pass.approve')
                                        @if($gatePass->status === 'pending')
                                            <form method="POST" action="{{ route('inventory.gate-passes.approve', $gatePass) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Issue this gate pass and release goods?')">Issue</button>
                                            </form>
                                        @endif
                                    @endcan
                                    @can('inv_shipment.add')
                                        @if($gatePass->status === 'issued')
                                            <a href="{{ route('inventory.shipments.create', ['gate_pass_id' => $gatePass->id]) }}" class="btn btn-sm btn-outline-secondary">Ship</a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No gate passes found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $gatePasses->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@include('sfl-inventory::admin.partials.select2-init')
@endsection
