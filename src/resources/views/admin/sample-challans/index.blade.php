@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Sample Delivery Challan') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Sample Delivery Challan</h4>
            @can('inv_sample_challan.add')
                <a href="{{ route('inventory.sample-challans.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> New Challan</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search Challan No" value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="store_id" class="form-control form-control-sm inv-select2">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From">
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To">
                </div>
                <div class="col-md-3 mb-2 d-flex align-items-end flex-wrap gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                    <a href="{{ route('inventory.sample-challans.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Challan No</th><th>Date</th><th>Store</th><th>To</th><th>Lines</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($challans as $challan)
                            <tr>
                                <td>{{ $loop->iteration + $challans->firstItem() - 1 }}</td>
                                <td>{{ $challan->challan_no }}</td>
                                <td>{{ $challan->challan_date->format('d M Y') }}</td>
                                <td>{{ $challan->store->name ?? '-' }}</td>
                                <td>{{ $challan->to_name }}</td>
                                <td>{{ $challan->items_count }}</td>
                                <td class="text-right">
                                    @can('inv_sample_challan.view')
                                        <a href="{{ route('inventory.sample-challans.show', $challan) }}" class="btn-custom success"><i class="fa-solid fa-eye"></i></a>
                                    @endcan
                                    @can('inv_sample_challan.print')
                                        <a href="{{ route('inventory.sample-challans.print', $challan) }}" target="_blank" class="btn-custom primary"><i class="fa-solid fa-print"></i></a>
                                    @endcan
                                    @can('inv_sample_challan.edit')
                                        <a href="{{ route('inventory.sample-challans.edit', $challan) }}" class="btn-custom yellow"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('inv_sample_challan.delete')
                                        <button type="button" class="btn-custom danger" data-toggle="modal" data-target="#deleteSampleChallanModal" data-action="{{ route('inventory.sample-challans.destroy', $challan) }}"><i class="fa-solid fa-trash"></i></button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No challans found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $challans->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteSampleChallanModal', 'label' => 'challan'])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
