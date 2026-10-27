@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Challan ' . $challan->challan_no) }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Challan — {{ $challan->challan_no }}</h4>
            <div>
                @can('inv_sample_challan.print')
                    <a href="{{ route('inventory.sample-challans.print', $challan) }}" target="_blank" class="btn btn-sm ui-act ui-act-print" title="Print"><i class="fa-solid fa-print"></i> Print</a>
                @endcan
                @can('inv_sample_challan.edit')
                    <a href="{{ route('inventory.sample-challans.edit', $challan) }}" class="btn btn-sm ui-act ui-act-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                @endcan
                <a href="{{ route('inventory.sample-challans.index') }}" class="btn btn-sm ui-act ui-act-back" title="Back"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3"><strong>Date:</strong> {{ $challan->challan_date->format('d M Y') }}</div>
                <div class="col-md-3"><strong>Store:</strong> {{ $challan->store->name ?? '-' }}</div>
                <div class="col-md-3"><strong>To:</strong> {{ $challan->to_name }}</div>
                <div class="col-md-3"><strong>Contact:</strong> {{ $challan->to_contact ?: '-' }}</div>
                <div class="col-md-8 mt-2"><strong>Address:</strong> {{ $challan->to_address ?: '-' }}</div>
                <div class="col-md-4 mt-2"><strong>Issued by:</strong> {{ $challan->issuer->name ?? '-' }}</div>
                @if($challan->remarks)
                    <div class="col-12 mt-2"><strong>Remarks:</strong> {{ $challan->remarks }}</div>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr><th>#</th><th>Item</th><th>PO</th><th>Style</th><th>Colour</th><th>Size</th><th>Sample of Buyer</th><th>{{ $challan->ppColumnLabel() }}</th><th>Total</th><th>Unit</th><th>Remarks</th></tr>
                    </thead>
                    <tbody>
                        @forelse($challan->items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->item_name ?: '-' }}</td>
                                <td>{{ $item->po_no ?: '-' }}</td>
                                <td>{{ $item->style_no ?: '-' }}</td>
                                <td>{{ $item->color_name ?: '-' }}</td>
                                <td>{{ $item->size->name ?? '-' }}</td>
                                <td class="text-right">{{ $item->buyer_sample_qty }}</td>
                                <td class="text-right">{{ $item->pp_sample_qty }}</td>
                                <td class="text-right">{{ $item->total_qty }}</td>
                                <td>{{ $item->unit }}</td>
                                <td>{{ $item->remarks }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-muted">No items.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
