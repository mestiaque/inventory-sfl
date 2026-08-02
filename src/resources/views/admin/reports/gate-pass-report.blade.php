@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Gate Pass Report') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Gate Pass Report</h5></div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="status" class="form-control inv-select2">
                        <option value="">All Status</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="issued" @selected(request('status') === 'issued')>Issued</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From"></div>
                <div class="col-md-3"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To"></div>
                <div class="col-md-3"><button type="submit" class="btn btn-secondary">Filter</button></div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead><tr><th>Gate Pass No</th><th>Buyer</th><th>Store</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($gatePasses as $gatePass)
                            <tr>
                                <td>{{ $gatePass->gate_pass_no }}</td>
                                <td>{{ $gatePass->buyer?->name }}</td>
                                <td>{{ $gatePass->store?->name }}</td>
                                <td>{{ $gatePass->gate_pass_date?->format('d M Y') }}</td>
                                <td>{{ ucfirst($gatePass->status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No gate passes found.</td></tr>
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
