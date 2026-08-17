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
                    <select name="store_id" class="form-control inv-select2">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
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
                            <th>#</th><th>Code</th><th>Name</th><th>Category</th><th>Department</th><th>Supplier</th><th>Buyer</th><th>Unit</th><th>Type</th><th>Store</th><th>Status</th><th class="text-end">Actions</th>
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
                                <td>{{ $item->buyer?->name ?? '—' }}</td>
                                <td>{{ $item->unit?->short_name }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $item->item_type)) }}</td>
                                <td>{{ $item->openingStore?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ $item->is_active ? 'success' : 'secondary' }}">
                                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#viewItemModal{{ $item->id }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @can('inv_item.edit')
                                        <a href="{{ route('inventory.items.edit', $item) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @endcan
                                    @can('inv_item.delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteItemModal" data-action="{{ route('inventory.items.destroy', $item) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endcan
                                    @can('inv_item.force_delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger d-none" title="Force Delete (wipes stock history too)" data-toggle="modal" data-target="#forceDeleteItemModal" data-action="{{ route('inventory.items.force-destroy', $item) }}" data-item-name="{{ $item->item_code }} — {{ $item->item_name }}">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>

                            <div class="modal fade" id="viewItemModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Item Details — {{ $item->item_code }}</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-3">Item Code</dt><dd class="col-sm-9">{{ $item->item_code }}</dd>
                                                <dt class="col-sm-3">Item Name</dt><dd class="col-sm-9">{{ $item->item_name }}</dd>
                                                <dt class="col-sm-3">Category</dt><dd class="col-sm-9">{{ $item->category?->name ?? '—' }}</dd>
                                                <dt class="col-sm-3">Department</dt><dd class="col-sm-9">{{ $item->department?->name ?? '—' }}</dd>
                                                <dt class="col-sm-3">Supplier</dt><dd class="col-sm-9">{{ $item->supplier?->name ?? '—' }}</dd>
                                                <dt class="col-sm-3">Buyer</dt><dd class="col-sm-9">{{ $item->buyer?->name ?? '—' }}</dd>
                                                <dt class="col-sm-3">Unit</dt><dd class="col-sm-9">{{ $item->unit?->name ?? '—' }} @if($item->unit?->short_name) ({{ $item->unit->short_name }}) @endif</dd>
                                                <dt class="col-sm-3">Brand</dt><dd class="col-sm-9">{{ $item->brand?->name ?? '—' }}</dd>
                                                <dt class="col-sm-3">Color</dt><dd class="col-sm-9">{{ $item->color?->name ?? '—' }}</dd>
                                                <dt class="col-sm-3">Size</dt><dd class="col-sm-9">{{ $item->size?->name ?? '—' }}</dd>
                                                <dt class="col-sm-3">Type</dt><dd class="col-sm-9">{{ $item->item_type ? ucwords(str_replace('_', ' ', $item->item_type)) : '—' }}</dd>
                                                <dt class="col-sm-3">Store</dt><dd class="col-sm-9">{{ $item->openingStore?->name ?? '—' }}</dd>
                                                <dt class="col-sm-3">Specification</dt><dd class="col-sm-9">{{ $item->specification ?: '—' }}</dd>
                                                <dt class="col-sm-3">Status</dt>
                                                <dd class="col-sm-9">
                                                    <span class="badge p-1 text-white bg-{{ $item->is_active ? 'success' : 'secondary' }}">
                                                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </dd>
                                            </dl>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr><td colspan="12" class="text-center text-muted">No items found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $items->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteItemModal', 'label' => 'item'])

<div class="modal fade" id="forceDeleteItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="forceDeleteItemModalForm">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Force Delete Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">Permanently delete <strong id="forceDeleteItemName"></strong>?</p>
                    <p class="text-danger mb-0">This wipes the item's entire stock ledger (all stock transactions) and removes the item itself — it cannot be undone. If this item is used on any real GRN, Purchase Order, Requisition, Issue, etc., the delete will be blocked instead.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Force Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('js')
<script>
    $('#forceDeleteItemModal').on('show.bs.modal', function (event) {
        const trigger = $(event.relatedTarget);
        $('#forceDeleteItemModalForm').attr('action', trigger.data('action'));
        $('#forceDeleteItemName').text(trigger.data('item-name'));
    });
</script>
@endpush

@include('sfl-inventory::admin.partials.select2-init')
@endsection
