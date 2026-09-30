@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Requisition For') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Requisition For</h4>
            <div>
                @can('inv_requisition_purpose.delete')
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#purposeTrashModal">
                        <i class="fa-solid fa-trash"></i> Trash ({{ $trashedPurposes->count() }})
                    </button>
                @endcan
                @can('inv_requisition_purpose.add')
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createPurposeModal">
                        <i class="fa-solid fa-plus"></i> Add Option
                    </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name / code" value="{{ request('search') }}">
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
                    <a href="{{ route('inventory.requisition-purposes.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Name</th><th>Code</th><th>Sort</th><th>Status</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($purposes as $purpose)
                            <tr>
                                <td>{{ $loop->iteration + $purposes->firstItem() - 1 }}</td>
                                <td>{{ $purpose->name }}</td>
                                <td><code>{{ $purpose->code }}</code></td>
                                <td>{{ $purpose->sort_order }}</td>
                                <td>
                                    <span class="badge badge-{{ $purpose->is_active ? 'success' : 'secondary' }}">
                                        {{ $purpose->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn-custom success" data-toggle="modal" data-target="#viewPurposeModal{{ $purpose->id }}"><i class="fa-solid fa-eye"></i></button>
                                    @can('inv_requisition_purpose.edit')
                                        <button type="button" class="btn-custom yellow" data-toggle="modal" data-target="#editPurposeModal{{ $purpose->id }}"><i class="fa-solid fa-pen"></i></button>
                                    @endcan
                                    @can('inv_requisition_purpose.delete')
                                        <button type="button" class="btn-custom danger" data-toggle="modal" data-target="#deletePurposeModal" data-action="{{ route('inventory.requisition-purposes.destroy', $purpose) }}"><i class="fa-solid fa-trash"></i></button>
                                    @endcan
                                </td>
                            </tr>
                            <div class="modal fade" id="viewPurposeModal{{ $purpose->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Requisition For Details</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $purpose->name }}</dd>
                                                <dt class="col-sm-4">Code</dt><dd class="col-sm-8"><code>{{ $purpose->code }}</code></dd>
                                                <dt class="col-sm-4">Sort Order</dt><dd class="col-sm-8">{{ $purpose->sort_order }}</dd>
                                                <dt class="col-sm-4">Status</dt>
                                                <dd class="col-sm-8">
                                                    <span class="badge badge-{{ $purpose->is_active ? 'success' : 'secondary' }}">
                                                        {{ $purpose->is_active ? 'Active' : 'Inactive' }}
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
                            @can('inv_requisition_purpose.edit')
                                <div class="modal fade" id="editPurposeModal{{ $purpose->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('inventory.requisition-purposes.update', $purpose) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Option</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $purpose->name }}" required>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-7 mb-3">
                                                            <label class="form-label">Code</label>
                                                            <input type="text" name="code" class="form-control form-control-sm" value="{{ $purpose->code }}" @if($purpose->isReferenced()) readonly title="Used by requisitions — can't change" @endif>
                                                        </div>
                                                        <div class="col-5 mb-3">
                                                            <label class="form-label">Sort Order</label>
                                                            <input type="number" min="0" name="sort_order" class="form-control form-control-sm" value="{{ $purpose->sort_order }}">
                                                        </div>
                                                    </div>
                                                    <div class="custom-control custom-switch mb-4">
                                                        <input type="hidden" name="is_active" value="0">
                                                        <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="purposeActive{{ $purpose->id }}" @checked($purpose->is_active)>
                                                        <label class="custom-control-label" for="purposeActive{{ $purpose->id }}">Active</label>
                                                    </div>
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
                            <tr><td colspan="6" class="text-center text-muted">No options found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $purposes->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@can('inv_requisition_purpose.add')
    <div class="modal fade" id="createPurposeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('inventory.requisition-purposes.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Option</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name') }}" required>
                        </div>
                        <div class="row">
                            <div class="col-7 mb-3">
                                <label class="form-label">Code <small class="text-muted">(optional — made from the name)</small></label>
                                <input type="text" name="code" class="form-control form-control-sm" value="{{ old('code') }}" placeholder="e.g. machine_parts">
                            </div>
                            <div class="col-5 mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" min="0" name="sort_order" class="form-control form-control-sm" value="{{ old('sort_order') }}" placeholder="auto">
                            </div>
                        </div>
                        <div class="custom-control custom-switch mb-4">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="purposeActiveNew" checked>
                            <label class="custom-control-label" for="purposeActiveNew">Active</label>
                        </div>
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

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deletePurposeModal', 'label' => 'option'])
@include('sfl-inventory::admin.partials.trash-modal', [
    'modalId' => 'purposeTrashModal',
    'label' => 'Requisition For',
    'rows' => $trashedPurposes,
    'restoreRoute' => 'inventory.requisition-purposes.restore',
    'forceRoute' => 'inventory.requisition-purposes.force-destroy',
    'canRestore' => auth()->user()->can('inv_requisition_purpose.delete'),
    'canForce' => auth()->user()->can('inv_requisition_purpose.force_delete'),
])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
