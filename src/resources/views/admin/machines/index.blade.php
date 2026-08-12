@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Machines') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Machines</h5>
            @can('inv_machine.add')
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createMachineModal">
                    <i class="fa-solid fa-plus"></i> Add Machine
                </button>
            @endcan
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search name or code" value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="department_id" class="form-control inv-select2">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
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
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('inventory.machines.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Name</th><th>Code</th><th>Model</th><th>Type</th><th>Department</th><th>Section</th><th>Line</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($machines as $machine)
                            <tr>
                                <td>{{ $loop->iteration + $machines->firstItem() - 1 }}</td>
                                <td>{{ $machine->name }}</td>
                                <td>{{ $machine->code }}</td>
                                <td>{{ $machine->model }}</td>
                                <td>{{ $machine->type }}</td>
                                <td>{{ $machine->department?->name ?? '—' }}</td>
                                <td>{{ $machine->section }}</td>
                                <td>{{ $machine->line }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ $machine->is_active ? 'success' : 'secondary' }}">
                                        {{ $machine->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#viewMachineModal{{ $machine->id }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @can('inv_machine.edit')
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editMachineModal{{ $machine->id }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    @endcan
                                    @can('inv_machine.delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteMachineModal" data-action="{{ route('inventory.machines.destroy', $machine) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                            <div class="modal fade" id="viewMachineModal{{ $machine->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Machine Details</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $machine->name }}</dd>
                                                <dt class="col-sm-4">Code</dt><dd class="col-sm-8">{{ $machine->code }}</dd>
                                                <dt class="col-sm-4">Model</dt><dd class="col-sm-8">{{ $machine->model ?? '—' }}</dd>
                                                <dt class="col-sm-4">Origin</dt><dd class="col-sm-8">{{ $machine->origin ?? '—' }}</dd>
                                                <dt class="col-sm-4">Type</dt><dd class="col-sm-8">{{ $machine->type ?? '—' }}</dd>
                                                <dt class="col-sm-4">Color</dt><dd class="col-sm-8">{{ $machine->color ?? '—' }}</dd>
                                                <dt class="col-sm-4">Department</dt><dd class="col-sm-8">{{ $machine->department?->name ?? '—' }}</dd>
                                                <dt class="col-sm-4">Section</dt><dd class="col-sm-8">{{ $machine->section ?? '—' }}</dd>
                                                <dt class="col-sm-4">Line</dt><dd class="col-sm-8">{{ $machine->line ?? '—' }}</dd>
                                                <dt class="col-sm-4">Description</dt><dd class="col-sm-8">{{ $machine->description ?? '—' }}</dd>
                                                <dt class="col-sm-4">Status</dt>
                                                <dd class="col-sm-8">
                                                    <span class="badge p-1 text-white bg-{{ $machine->is_active ? 'success' : 'secondary' }}">
                                                        {{ $machine->is_active ? 'Active' : 'Inactive' }}
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
                            @can('inv_machine.edit')
                                <div class="modal fade" id="editMachineModal{{ $machine->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('inventory.machines.update', $machine) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Machine</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                </div>
                                                <div class="modal-body">
                                                    @include('sfl-inventory::admin.machines.partials.fields', ['machine' => $machine, 'departments' => $departments])
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
                            <tr><td colspan="10" class="text-center text-muted">No machines found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $machines->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@can('inv_machine.add')
    <div class="modal fade" id="createMachineModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('inventory.machines.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Machine</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @include('sfl-inventory::admin.machines.partials.fields', ['machine' => null, 'departments' => $departments])
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

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteMachineModal', 'label' => 'machine'])
@include('sfl-inventory::admin.partials.select2-init')
@endsection
