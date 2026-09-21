@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add Store Order') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Add Store Order — against {{ $purchaseRequisition->requisition_no }}</h5>
            <a href="{{ route('inventory.purchase-requisitions.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.purchase-orders.store') }}">
                @csrf
                <input type="hidden" name="purchase_requisition_id" value="{{ $purchaseRequisition->id }}">

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Order Date <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" class="form-control" value="{{ old('order_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Expected Date</label>
                        <input type="date" name="expected_date" class="form-control" value="{{ old('expected_date') }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Items</h6>
                    <span class="text-muted" style="font-size:12px;">Requisitioned on {{ $purchaseRequisition->requisition_no }} — only its requested items can be ordered here.</span>
                </div>
                <p class="text-muted" style="font-size:12px;">Price isn't entered here — it's captured at Store Receive time, against the actual supplier challan.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead>
                            <tr><th style="min-width:220px">Item</th><th style="width:90px">Unit</th><th style="width:110px">Color</th><th style="width:110px">Size</th><th style="width:130px">Remaining Approved</th><th style="width:150px">Quantity</th></tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseRequisition->items as $index => $line)
                                @php $remaining = $line->approved_qty - $line->converted_qty; @endphp
                                @continue($remaining <= 0)
                                <tr>
                                    <td>
                                        {{ $line->item?->item_code }} — {{ $line->item?->item_name }}
                                        <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $line->item_id }}">
                                        <input type="hidden" name="items[{{ $index }}][purchase_requisition_item_id]" value="{{ $line->id }}">
                                    </td>
                                    <td>{{ $line->item?->unit?->short_name ?? '—' }}</td>
                                    <td>{{ $line->color?->name ?? '—' }}</td>
                                    <td>{{ $line->size?->name ?? '—' }}</td>
                                    <td>{{ inv_qty($remaining) }}</td>
                                    <td><input type="number" step="0.0001" min="0.0001" max="{{ $remaining }}" name="items[{{ $index }}][quantity]" class="form-control" value="{{ old('items.' . $index . '.quantity', $remaining) }}" required></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Save Store Order</button>
                <a href="{{ route('inventory.purchase-requisitions.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@endsection
