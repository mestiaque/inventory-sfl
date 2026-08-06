@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Stores') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Stores</h5>
            @can('inv_store.add')
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createStoreModal">
                    <i class="fa-solid fa-plus"></i> Add Store
                </button>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search name or code" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control inv-select2">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('inventory.stores.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Store For</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stores as $store)
                            <tr>
                                <td>{{ $loop->iteration + $stores->firstItem() - 1 }}</td>
                                <td>{{ $store->name }}</td>
                                <td>{{ $store->code }}</td>
                                <td>{{ ['raw_material' => 'For Buyer', 'accessories' => 'For Accessories', 'finished_goods' => 'For Finished Goods'][$store->type] ?? ucfirst($store->type) }}</td>
                                <td>{{ $store->address }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ $store->is_active ? 'success' : 'secondary' }}">
                                        {{ $store->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal"
                                        data-target="#viewStoreModal{{ $store->id }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @can('inv_store.edit')
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal"
                                            data-target="#editStoreModal{{ $store->id }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    @endcan
                                    @can('inv_store.delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal"
                                            data-target="#deleteStoreModal" data-action="{{ route('inventory.stores.destroy', $store) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>

                            <div class="modal fade" id="viewStoreModal{{ $store->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Store Details</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $store->name }}</dd>
                                                <dt class="col-sm-4">Code</dt><dd class="col-sm-8">{{ $store->code }}</dd>
                                                <dt class="col-sm-4">Store For</dt><dd class="col-sm-8">{{ ['raw_material' => 'For Buyer', 'accessories' => 'For Accessories', 'finished_goods' => 'For Finished Goods'][$store->type] ?? ucfirst($store->type) }}</dd>
                                                <dt class="col-sm-4">Address</dt><dd class="col-sm-8">{{ $store->address ?: '—' }}</dd>
                                                <dt class="col-sm-4">Status</dt>
                                                <dd class="col-sm-8">
                                                    <span class="badge p-1 text-white bg-{{ $store->is_active ? 'success' : 'secondary' }}">
                                                        {{ $store->is_active ? 'Active' : 'Inactive' }}
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

                            @can('inv_store.edit')
                                <div class="modal fade" id="editStoreModal{{ $store->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('inventory.stores.update', $store) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Store</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                </div>
                                                <div class="modal-body">
                                                    @include('sfl-inventory::admin.stores.partials.fields', ['store' => $store])
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endcan
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No stores found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $stores->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@can('inv_store.add')
    <div class="modal fade" id="createStoreModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('inventory.stores.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Store</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @include('sfl-inventory::admin.stores.partials.fields', ['store' => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteStoreModal', 'label' => 'store'])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
