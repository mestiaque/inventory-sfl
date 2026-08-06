@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Operators / Store Incharge') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 p-4 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Operators / Store Incharge</h5>
            @can('inv_operator.add')
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createOperatorModal">
                    <i class="fa-solid fa-plus"></i> Add Operator
                </button>
            @endcan
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Operator</strong> sees only the entries they personally created. <strong>Store Incharge / Store Manager</strong> sees every entry for their assigned store, regardless of who created it. A user with no profile here (e.g. an admin) is never restricted.
            </div>

            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Search name" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="designation" class="form-control inv-select2">
                        <option value="">All Designations</option>
                        <option value="operator" @selected(request('designation') === 'operator')>Operator</option>
                        <option value="store_incharge" @selected(request('designation') === 'store_incharge')>Store Incharge</option>
                        <option value="store_manager" @selected(request('designation') === 'store_manager')>Store Manager</option>
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
                    <a href="{{ route('inventory.operators.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr><th>#</th><th>Name</th><th>Code</th><th>System Login</th><th>Designation</th><th>Assigned Store</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($operators as $operator)
                            <tr>
                                <td>{{ $loop->iteration + $operators->firstItem() - 1 }}</td>
                                <td>{{ $operator->name }}</td>
                                <td>{{ $operator->code }}</td>
                                <td>{{ $operator->user?->name }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ $operator->designation === 'operator' ? 'secondary' : 'info' }}">
                                        {{ ucwords(str_replace('_', ' ', $operator->designation)) }}
                                    </span>
                                </td>
                                <td>{{ $operator->store?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge p-1 text-white bg-{{ $operator->is_active ? 'success' : 'secondary' }}">
                                        {{ $operator->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#viewOperatorModal{{ $operator->id }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @can('inv_operator.edit')
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editOperatorModal{{ $operator->id }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    @endcan
                                    @can('inv_operator.delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteOperatorModal" data-action="{{ route('inventory.operators.destroy', $operator) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>

                            <div class="modal fade" id="viewOperatorModal{{ $operator->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Operator Details</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $operator->name }}</dd>
                                                <dt class="col-sm-4">Code</dt><dd class="col-sm-8">{{ $operator->code ?: '—' }}</dd>
                                                <dt class="col-sm-4">System Login</dt><dd class="col-sm-8">{{ $operator->user?->name ?? '—' }}</dd>
                                                <dt class="col-sm-4">Designation</dt>
                                                <dd class="col-sm-8">
                                                    <span class="badge p-1 text-white bg-{{ $operator->designation === 'operator' ? 'secondary' : 'info' }}">
                                                        {{ ucwords(str_replace('_', ' ', $operator->designation)) }}
                                                    </span>
                                                </dd>
                                                <dt class="col-sm-4">Assigned Store</dt><dd class="col-sm-8">{{ $operator->store?->name ?? '—' }}</dd>
                                                <dt class="col-sm-4">Status</dt>
                                                <dd class="col-sm-8">
                                                    <span class="badge p-1 text-white bg-{{ $operator->is_active ? 'success' : 'secondary' }}">
                                                        {{ $operator->is_active ? 'Active' : 'Inactive' }}
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

                            @can('inv_operator.edit')
                                <div class="modal fade" id="editOperatorModal{{ $operator->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('inventory.operators.update', $operator) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Operator</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                </div>
                                                <div class="modal-body">
                                                    @include('sfl-inventory::admin.operators.partials.fields', ['operator' => $operator])
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
                                <td colspan="8" class="text-center text-muted">No operators found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $operators->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@can('inv_operator.add')
    <div class="modal fade" id="createOperatorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('inventory.operators.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Operator</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @include('sfl-inventory::admin.operators.partials.fields', ['operator' => null])
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

@include('sfl-inventory::admin.partials.delete-confirm-modal', ['modalId' => 'deleteOperatorModal', 'label' => 'operator'])
@include('sfl-inventory::admin.partials.select2-init')
@push('js')
<script>
    function toggleOperatorStoreField(scope) {
        scope = scope || document;
        $(scope).find('.operator-designation').each(function () {
            const wrapper = $(this).closest('form').find('.operator-store-field');
            const update = () => wrapper.toggle($(this).val() !== 'operator');
            update();
            $(this).off('change.operatorStore').on('change.operatorStore', update);
        });
    }
    $(function () { toggleOperatorStoreField(document); });
</script>
@endpush
@endsection
