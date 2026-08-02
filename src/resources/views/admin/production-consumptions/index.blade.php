@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Production Consumption') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Production Consumption</h5>
            @can('inv_production.add')
                <a href="{{ route('inventory.production-consumptions.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Consumption</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search consumption no" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="department_id" class="form-control inv-select2">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
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
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('inventory.production-consumptions.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Consumption No</th><th>Department</th><th>Store</th><th>Style</th><th>Order Ref</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        @forelse($consumptions as $consumption)
                            <tr>
                                <td>{{ $loop->iteration + $consumptions->firstItem() - 1 }}</td>
                                <td>{{ $consumption->consumption_no }}</td>
                                <td>{{ $consumption->department?->name }}</td>
                                <td>{{ $consumption->store?->name }}</td>
                                <td>{{ $consumption->style }}</td>
                                <td>{{ $consumption->order_ref }}</td>
                                <td>{{ $consumption->consumption_date?->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No consumption records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $consumptions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@include('sfl-inventory::admin.partials.select2-init')
@endsection
