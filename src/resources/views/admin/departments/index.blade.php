@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Departments') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Departments</h5>
            @can('inv_department.add')
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createDepartmentModal">
                    <i class="fa-solid fa-plus"></i> Add Department
                </button>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search name" value="{{ request('search') }}">
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
                    <a href="{{ route('inventory.departments.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Name</th><th>Code</th><th>Default Floor Store</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $department)
                            <tr>
                                <td>{{ $loop->iteration + $departments->firstItem() - 1 }}</td>
                                <td>{{ $department->name }}</td>
                                <td>{{ $department->code }}</td>
                                <td>{{ $department->defaultStore?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ $department->is_active ? 'success' : 'secondary' }}">
                                        {{ $department->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#viewDepartmentModal{{ $department->id }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @can('inv_department.edit')
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editDepartmentModal{{ $department->id }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    @endcan
                                    @can('inv_department.delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteDepartmentModal" data-action="{{ route('inventory.departments.destroy', $department) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endcan
                                    @can('inv_department.force_delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Force Delete (removes department reference from all records too)" data-toggle="modal" data-target="#forceDeleteDepartmentModal" data-action="{{ route('inventory.departments.force-destroy', $department) }}" data-dept-name="{{ $department->name }} ({{ $department->code }})">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                            <div class="modal fade" id="viewDepartmentModal{{ $department->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Department Details</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $department->name }}</dd>
                                                <dt class="col-sm-4">Code</dt><dd class="col-sm-8">{{ $department->code }}</dd>
                                                <dt class="col-sm-4">Default Floor Store</dt><dd class="col-sm-8">{{ $department->defaultStore?->name ?? '—' }}</dd>
                                                <dt class="col-sm-4">Status</dt>
                                                <dd class="col-sm-8">
                                                    <span class="badge p-1 text-white bg-{{ $department->is_active ? 'success' : 'secondary' }}">
                                                        {{ $department->is_active ? 'Active' : 'Inactive' }}
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
                            @can('inv_department.edit')
                                <div class="modal fade" id="editDepartmentModal{{ $department->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('inventory.departments.update', $department) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Department</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                </div>
                                                <div class="modal-body">
                                                    @include('sfl-inventory::admin.departments.partials.fields', ['department' => $department, 'stores' => $stores])
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
                            <tr><td colspan="6" class="text-center text-muted">No departments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $departments->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@can('inv_department.add')
    <div class="modal fade" id="createDepartmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('inventory.departments.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Department</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @include('sfl-inventory::admin.departments.partials.fields', ['department' => null, 'stores' => $stores])
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

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteDepartmentModal', 'label' => 'department'])

<div class="modal fade" id="forceDeleteDepartmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="forceDeleteDepartmentModalForm">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Force Delete Department</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">Permanently delete <strong id="forceDeleteDeptName"></strong>?</p>
                    <p class="text-danger mb-0">This is blocked if the department is used on any real Requisition, Issue, or Production Consumption document. If clear, it permanently removes the department and clears its reference from the stock ledger, broken needle entries, machines, and items (those records themselves are kept).</p>
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
    $('#forceDeleteDepartmentModal').on('show.bs.modal', function (event) {
        const trigger = $(event.relatedTarget);
        $('#forceDeleteDepartmentModalForm').attr('action', trigger.data('action'));
        $('#forceDeleteDeptName').text(trigger.data('dept-name'));
    });
</script>
@endpush

@include('sfl-inventory::admin.partials.select2-init')
@endsection
