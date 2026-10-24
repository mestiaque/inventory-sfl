@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Edit Challan ' . $challan->challan_no) }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Edit Challan {{ $challan->challan_no }}</h4>
            <a href="{{ route('inventory.sample-challans.show', $challan) }}" class="btn btn-light btn-sm btn-icon" title="Back"><i class="fa-solid fa-arrow-left"></i></a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.sample-challans.update', $challan) }}">
                @csrf @method('PUT')
                @include('sfl-inventory::admin.sample-challans.partials.form')
                <button type="submit" class="btn btn-primary mt-3 btn-sm">Update Challan</button>
                <a href="{{ route('inventory.sample-challans.show', $challan) }}" class="btn btn-light mt-3 btn-sm">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
