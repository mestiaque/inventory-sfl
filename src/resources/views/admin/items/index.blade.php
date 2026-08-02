@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Item Master') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Item Master</h5>
            @can('inv_item.add')
                <a href="{{ route('inventory.items.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Add Item
                </a>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="item_code" class="form-control" placeholder="Search Code" value="{{ request('item_code') }}">
                </div>
                <div class="col-md-2">
                    <input type="text" name="item_name" class="form-control" placeholder="Search Name" value="{{ request('item_name') }}">
                </div>
                <div class="col-md-2">
                    <select name="category_id" class="form-control inv-select2">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="unit_id" class="form-control inv-select2">
                        <option value="">All Units</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->name }} ({{ $unit->short_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="item_type" class="form-control inv-select2">
                        <option value="">All Types</option>
                        <option value="raw_material" @selected(request('item_type') === 'raw_material')>Raw Material</option>
                        <option value="wip" @selected(request('item_type') === 'wip')>WIP</option>
                        <option value="finished_good" @selected(request('item_type') === 'finished_good')>Finished Good</option>
                    </select>
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
                    <select name="supplier_id" class="form-control inv-select2">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control inv-select2">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 mt-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-2 mt-2">
                    <a href="{{ route('inventory.items.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th><th>Code</th><th>Name</th><th>Category</th><th>Department</th><th>Supplier</th><th>Unit</th><th>Type</th><th>Min/Max</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $loop->iteration + $items->firstItem() - 1 }}</td>
                                <td>{{ $item->item_code }}</td>
                                <td>
                                    {{ $item->item_name }}
                                    @if($item->brand || $item->color || $item->size)
                                        <br><small class="text-muted">{{ collect([$item->brand?->name, $item->color?->name, $item->size?->name])->filter()->implode(' / ') }}</small>
                                    @endif
                                </td>
                                <td>{{ $item->category?->name }}</td>
                                <td>{{ $item->department?->name ?? '—' }}</td>
                                <td>{{ $item->supplier?->name ?? '—' }}</td>
                                <td>{{ $item->unit?->short_name }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $item->item_type)) }}</td>
                                <td>{{ rtrim(rtrim($item->minimum_stock, '0'), '.') }} / {{ rtrim(rtrim($item->maximum_stock, '0'), '.') }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ $item->is_active ? 'success' : 'secondary' }}">
                                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @can('inv_item.edit')
                                        <a href="{{ route('inventory.items.edit', $item) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('inv_item.delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteItemModal" data-action="{{ route('inventory.items.destroy', $item) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-muted">No items found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $items->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteItemModal', 'label' => 'item'])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
