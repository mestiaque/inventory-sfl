@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Edit Item') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Edit Item — {{ $item->item_code }}</h5>
            <a href="{{ route('inventory.items.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.items.update', $item) }}">
                @csrf
                @method('PUT')
                @include('sfl-inventory::admin.items.partials.fields')
                <button type="submit" class="btn btn-primary">Update Item</button>
                <a href="{{ route('inventory.items.index') }}" class="btn btn-light">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@endsection
