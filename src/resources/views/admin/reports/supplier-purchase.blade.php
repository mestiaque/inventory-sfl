@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Supplier Wise Purchase') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Supplier Wise Purchase</h5></div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="supplier_id" class="form-control inv-select2">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From"></div>
                <div class="col-md-3"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To"></div>
                <div class="col-md-3"><button type="submit" class="btn btn-secondary">Filter</button></div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead><tr><th>Supplier</th><th class="text-end">Total Received Qty</th><th class="text-end">Total Amount</th></tr></thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->supplier_name }}</td>
                                <td class="text-end">{{ number_format($row->total_qty, 4) }}</td>
                                <td class="text-end">{{ number_format($row->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No purchases found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@include('sfl-inventory::admin.partials.select2-init')
@endsection
