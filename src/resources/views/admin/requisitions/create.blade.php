@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add Requisition') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Add Store Requisition</h5>
            <a href="{{ route('inventory.requisitions.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.requisitions.store') }}">
                @csrf
                @include('sfl-inventory::admin.requisitions.partials.form')
                <button type="submit" class="btn btn-primary mt-3">Submit Requisition</button>
                <a href="{{ route('inventory.requisitions.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
