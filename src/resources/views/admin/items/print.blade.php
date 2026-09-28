@php $printMode = true; @endphp
@extends(request()->boolean('excel_export') ? 'sfl-inventory::export-minimal' : 'printMaster2')

@section('title')
    {{ websiteTitle('Item Master') }}
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.reports.partials.print-header', ['title' => 'Item Master'])

    @php
        $filters = collect([
            'Store'    => request('store_id') === 'none' ? 'No store assigned' : (request('store_id') ? \ME\SflInventory\Models\InvStore::find(request('store_id'))?->name : null),
            'Category' => request('category_id') ? \ME\SflInventory\Models\InvItemCategory::find(request('category_id'))?->name : null,
            'Status'   => request('status') ? ucfirst(request('status')) : null,
            'Type'     => request('item_type') ? ucwords(str_replace('_', ' ', request('item_type'))) : null,
        ])->filter();
    @endphp
    @if($filters->isNotEmpty())
        <p style="font-size:12px; margin:4px 0 8px;">
            {!! $filters->map(fn ($v, $k) => '<strong>' . e($k) . ':</strong> ' . e($v))->implode(' &nbsp;|&nbsp; ') !!}
        </p>
    @endif

    <table class="table table-bordered table-sm" style="font-size:11px; width:100%;">
        <thead>
            <tr>
                <th>#</th><th>Code</th><th>Name</th><th>Brand</th><th>Category</th><th>Department</th><th>Supplier</th><th>Buyer</th>
                <th>Unit</th><th>Type</th><th>Store</th><th style="text-align:right">Min Stock</th><th style="text-align:right">Current Stock</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->item_code }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->brand?->name }}</td>
                    <td>{{ $item->category?->name }}</td>
                    <td>{{ $item->department?->name }}</td>
                    <td>{{ $item->supplier?->name }}</td>
                    <td>{{ $item->buyer?->name }}</td>
                    <td>{{ $item->unit?->short_name }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', (string) $item->item_type)) }}</td>
                    <td>{{ $item->openingStore?->name ?? '—' }}</td>
                    <td style="text-align:right">{{ $item->minimum_stock !== null ? inv_qty($item->minimum_stock) : '' }}</td>
                    <td style="text-align:right">{{ inv_qty($stock[$item->id] ?? 0) }}</td>
                    <td>{{ $item->is_active ? 'Active' : 'Inactive' }}</td>
                </tr>
            @empty
                <tr><td colspan="14" style="text-align:center">No items found.</td></tr>
            @endforelse
        </tbody>
        @if($items->isNotEmpty())
            <tfoot>
                <tr style="font-weight:bold">
                    <td colspan="14">
                        Total: {{ $items->count() }} item(s) — Active {{ $items->where('is_active', true)->count() }}, Inactive {{ $items->where('is_active', false)->count() }},
                        No store {{ $items->whereNull('opening_store_id')->count() }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
@endsection
