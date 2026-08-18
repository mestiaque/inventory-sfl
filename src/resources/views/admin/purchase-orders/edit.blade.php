@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Edit Store Order') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Edit Store Order — {{ $purchaseOrder->po_number }}</h5>
            <a href="{{ route('inventory.purchase-orders.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.purchase-orders.update', $purchaseOrder) }}">
                @csrf
                @method('PUT')
                @include('sfl-inventory::admin.purchase-orders.partials.form')
                <button type="submit" class="btn btn-primary mt-3">Update Store Order</button>
                <a href="{{ route('inventory.purchase-orders.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
