@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Colors') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Colors</h4>
            <div>
                @can('inv_color.delete')
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#colorTrashModal">
                        <i class="fa-solid fa-trash"></i> Trash ({{ $trashedColors->count() }})
                    </button>
                @endcan
                @can('inv_color.add')
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createColorModal">
                        <i class="fa-solid fa-plus"></i> Add Color
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
                    <a href="{{ route('inventory.colors.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Swatch</th><th>Name</th><th>Status</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($colors as $color)
                            <tr>
                                <td>{{ $loop->iteration + $colors->firstItem() - 1 }}</td>
                                <td>
                                    @if($color->hex_code)
                                        <span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:{{ $color->hex_code }};border:1px solid #ddd;"></span>
                                    @endif
                                </td>
                                <td>{{ $color->name }}</td>
                                <td>
                                    <span class="badge badge-{{ $color->is_active ? 'success' : 'secondary' }}">
                                        {{ $color->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn-custom success" data-toggle="modal" data-target="#viewColorModal{{ $color->id }}"><i class="fa-solid fa-eye"></i></button>
                                    @can('inv_color.edit')
                                        <button type="button" class="btn-custom yellow" data-toggle="modal" data-target="#editColorModal{{ $color->id }}"><i class="fa-solid fa-pen"></i></button>
                                    @endcan
                                    @can('inv_color.delete')
                                        <button type="button" class="btn-custom danger" data-toggle="modal" data-target="#deleteColorModal" data-action="{{ route('inventory.colors.destroy', $color) }}"><i class="fa-solid fa-trash"></i></button>
                                    @endcan
                                </td>
                            </tr>
                            <div class="modal fade" id="viewColorModal{{ $color->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Color Details</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $color->name }}</dd>
                                                <dt class="col-sm-4">Hex Code</dt>
                                                <dd class="col-sm-8">
                                                    @if($color->hex_code)
                                                        <span style="display:inline-block;width:16px;height:16px;border-radius:4px;vertical-align:middle;background:{{ $color->hex_code }};border:1px solid #ddd;"></span>
                                                        {{ $color->hex_code }}
                                                    @else
                                                        —
                                                    @endif
                                                </dd>
                                                <dt class="col-sm-4">Status</dt>
                                                <dd class="col-sm-8">
                                                    <span class="badge badge-{{ $color->is_active ? 'success' : 'secondary' }}">
                                                        {{ $color->is_active ? 'Active' : 'Inactive' }}
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
                            @can('inv_color.edit')
                                <div class="modal fade" id="editColorModal{{ $color->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('inventory.colors.update', $color) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Color</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
<div class="col-md-3 mb-3">
                                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $color->name }}" required>
                                                    </div>
                                                    <div class="col-md-3 mb-3">
                                                        <label class="form-label">Hex Code</label>
                                                        <input type="color" name="hex_code" class="form-control form-control-sm form-control-color" value="{{ $color->hex_code ?: '#000000' }}">
                                                    </div>
                                                    <div class="col-md-3 mb-3 d-flex align-items-center"><div class="custom-control custom-switch mb-4">
                                                        <input type="hidden" name="is_active" value="0">
                                                        <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="colorActive{{ $color->id }}" @checked($color->is_active)>
                                                        <label class="custom-control-label" for="colorActive{{ $color->id }}">Active</label>
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
                            <tr><td colspan="5" class="text-center text-muted">No colors found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $colors->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@can('inv_color.add')
    <div class="modal fade" id="createColorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('inventory.colors.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Color</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
<div class="col-md-3 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Hex Code</label>
                            <input type="color" name="hex_code" class="form-control form-control-sm form-control-color" value="#000000">
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-center"><div class="custom-control custom-switch mb-4">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="colorActiveNew" checked>
                            <label class="custom-control-label" for="colorActiveNew">Active</label>
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

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteColorModal', 'label' => 'color'])
@include('sfl-inventory::admin.partials.trash-modal', [
    'modalId' => 'colorTrashModal',
    'label' => 'Color',
    'rows' => $trashedColors,
    'restoreRoute' => 'inventory.colors.restore',
    'forceRoute' => 'inventory.colors.force-destroy',
    'canRestore' => auth()->user()->can('inv_color.delete'),
    'canForce' => auth()->user()->can('inv_color.force_delete'),
])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
