@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Sizes') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Sizes</h4>
            <div>
                @can('inv_size.delete')
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#sizeTrashModal">
                        <i class="fa-solid fa-trash"></i> Trash ({{ $trashedSizes->count() }})
                    </button>
                @endcan
                @can('inv_size.add')
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createSizeModal">
                        <i class="fa-solid fa-plus"></i> Add Size
                    </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name" value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control form-control-sm inv-select2">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 d-flex align-items-end flex-wrap gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                    <a href="{{ route('inventory.sizes.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Name</th><th>Sort Order</th><th>Status</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($sizes as $size)
                            <tr>
                                <td>{{ $loop->iteration + $sizes->firstItem() - 1 }}</td>
                                <td>{{ $size->name }}</td>
                                <td>{{ $size->sort_order }}</td>
                                <td>
                                    <span class="badge badge-{{ $size->is_active ? 'success' : 'secondary' }}">
                                        {{ $size->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn-custom success" data-toggle="modal" data-target="#viewSizeModal{{ $size->id }}"><i class="fa-solid fa-eye"></i></button>
                                    @can('inv_size.edit')
                                        <button type="button" class="btn-custom yellow" data-toggle="modal" data-target="#editSizeModal{{ $size->id }}"><i class="fa-solid fa-pen"></i></button>
                                    @endcan
                                    @can('inv_size.delete')
                                        <button type="button" class="btn-custom danger" data-toggle="modal" data-target="#deleteSizeModal" data-action="{{ route('inventory.sizes.destroy', $size) }}"><i class="fa-solid fa-trash"></i></button>
                                    @endcan
                                </td>
                            </tr>
                            <div class="modal fade" id="viewSizeModal{{ $size->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Size Details</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $size->name }}</dd>
                                                <dt class="col-sm-4">Sort Order</dt><dd class="col-sm-8">{{ $size->sort_order }}</dd>
                                                <dt class="col-sm-4">Status</dt>
                                                <dd class="col-sm-8">
                                                    <span class="badge badge-{{ $size->is_active ? 'success' : 'secondary' }}">
                                                        {{ $size->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </dd>
                                            </dl>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @can('inv_size.edit')
                                <div class="modal fade" id="editSizeModal{{ $size->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('inventory.sizes.update', $size) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Size</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
<div class="col-md-3 mb-3">
                                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $size->name }}" required>
                                                    </div>
                                                    <div class="col-md-3 mb-3">
                                                        <label class="form-label">Sort Order</label>
                                                        <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $size->sort_order }}">
                                                    </div>
                                                    <div class="col-md-3 mb-3 d-flex align-items-center"><div class="custom-control custom-switch mb-4">
                                                        <input type="hidden" name="is_active" value="0">
                                                        <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="sizeActive{{ $size->id }}" @checked($size->is_active)>
                                                        <label class="custom-control-label" for="sizeActive{{ $size->id }}">Active</label>
                                                    </div>
</div></div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endcan
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No sizes found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $sizes->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@can('inv_size.add')
    <div class="modal fade" id="createSizeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('inventory.sizes.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Size</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
<div class="col-md-3 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-center"><div class="custom-control custom-switch mb-4">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="sizeActiveNew" checked>
                            <label class="custom-control-label" for="sizeActiveNew">Active</label>
                        </div>
</div></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteSizeModal', 'label' => 'size'])
@include('sfl-inventory::admin.partials.trash-modal', [
    'modalId' => 'sizeTrashModal',
    'label' => 'Size',
    'rows' => $trashedSizes,
    'restoreRoute' => 'inventory.sizes.restore',
    'forceRoute' => 'inventory.sizes.force-destroy',
    'canRestore' => auth()->user()->can('inv_size.delete'),
    'canForce' => auth()->user()->can('inv_size.force_delete'),
])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
