{{-- props: purchaseOrder (optional, for edit), suppliers, items --}}
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Supplier <span class="text-danger">*</span></label>
        <select name="supplier_id" class="form-control inv-select2" required>
            <option value="">— Select —</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchaseOrder->supplier_id ?? '') == $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Order Date <span class="text-danger">*</span></label>
        <input type="date" name="order_date" class="form-control" value="{{ old('order_date', optional($purchaseOrder->order_date ?? null)->format('Y-m-d') ?? now()->toDateString()) }}" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Expected Date</label>
        <input type="date" name="expected_date" class="form-control" value="{{ old('expected_date', optional($purchaseOrder->expected_date ?? null)->format('Y-m-d')) }}">
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $purchaseOrder->remarks ?? '') }}</textarea>
    </div>
</div>

<hr>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Items</h6>
    <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="po"><i class="fa-solid fa-plus"></i> Add Row</button>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
        <thead>
            <tr><th style="min-width:220px">Item</th><th style="width:140px">Quantity</th><th style="width:140px">Rate</th><th style="width:140px">Amount</th><th style="width:40px"></th></tr>
        </thead>
        <tbody id="poRowsBody">
            @php $lines = old('items', isset($purchaseOrder) ? $purchaseOrder->items->map(fn ($i) => $i->toArray())->all() : [[]]); @endphp
            @foreach($lines as $index => $line)
                <tr>
                    <td>
                        <select name="items[{{ $index }}][item_id]" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" @selected(($line['item_id'] ?? null) == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][quantity]" class="form-control" data-role="qty" value="{{ $line['quantity'] ?? '' }}" required></td>
                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][rate]" class="form-control" data-role="rate" value="{{ $line['rate'] ?? '' }}" required></td>
                    <td><input type="text" class="form-control" data-role="amount" value="{{ $line['amount'] ?? '' }}" disabled></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<template id="poRowTemplate">
    <tr>
        <td>
            <select name="items[__INDEX__][item_id]" class="form-control inv-select2" required>
                <option value="">— Select —</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.0001" min="0.0001" name="items[__INDEX__][quantity]" class="form-control" data-role="qty" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][rate]" class="form-control" data-role="rate" required></td>
        <td><input type="text" class="form-control" data-role="amount" disabled></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
    </tr>
</template>
