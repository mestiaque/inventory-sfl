@php $printMode = $printMode ?? request()->boolean('print'); @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : ($printMode ? 'printMaster2' : adminTheme() . 'layouts.app'))

@section('title')
    @if($printMode)
        {{ websiteTitle('Supplier List') }}
    @else
        <title>{{ websiteTitle('Supplier List') }}</title>
    @endif
@endsection

@push('css')
<style>
.supplier-list-info-bar {
    display: flex;
    justify-content: space-between;
    background: #e9e9e9;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: bold;
    border: 1px solid #999;
    margin-bottom: 8px;
}
.supplier-list-table th { text-align: center; }
.supplier-list-table td.tc { text-align: center; }
</style>
@endpush

@section('contents')
<div class="flex-grow-1 inv-module">
    @if($printMode)
        @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Supplier List'])
    @else
        @include('sfl-inventory::admin.partials.alerts')
        @include('sfl-inventory::admin.partials.ui-kit')
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Supplier List</h5>
            @unless($printMode)
                @include('sfl-inventory::admin.reports.partials.export-print-buttons', ['report' => 'supplier-list'])
            @endunless
        </div>
        <div class="card-body">
            @unless($printMode)
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search name or code" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
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
                        <a href="{{ route('inventory.reports.supplier-list') }}" class="btn btn-light w-100">Reset</a>
                    </div>
                </form>
            @endunless

            <div class="supplier-list-info-bar">
                <span>Data Range: {{ $dataRangeLabel }}</span>
                <span>Date: {{ now()->format('d-m-Y') }}</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered supplier-list-table {{ $printMode ? '' : 'table-striped align-middle' }}">
                    <thead>
                        <tr>
                            <th>SL No.</th>
                            <th>Supplier Name</th>
                            <th>Products / Items Supplied</th>
                            <th>Business Relation Since</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Price Rating (1-5)</th>
                            <th>Quality Rating (1-5)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $i => $supplier)
                            <tr>
                                <td class="tc">{{ $i + 1 }}</td>
                                <td>{{ $supplier->name }}</td>
                                <td>{{ $supplier->products_supplied ?: '-' }}</td>
                                <td class="tc">{{ $supplier->relation_since_year ?: '-' }}</td>
                                <td>{{ $supplier->contact_person ?: '-' }}</td>
                                <td class="tc">{{ $supplier->phone ?: '-' }}</td>
                                <td>{{ $supplier->address ?: '-' }}</td>
                                <td class="tc">{{ $supplier->price_rating ?: '-' }}</td>
                                <td class="tc">{{ $supplier->quality_rating ?: '-' }}</td>
                                <td class="tc">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted">No suppliers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@unless($printMode)
    @include('sfl-inventory::admin.partials.select2-init')
@endunless
@endsection
