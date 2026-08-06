{{-- props: requisition (optional, for edit), departments, stores, items, buyers, employees --}}
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Department / Section <span class="text-danger">*</span></label>
        <select name="department_id" class="form-control inv-select2" required>
            <option value="">— Select —</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $requisition->department_id ?? '') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Issue From Store <span class="text-danger">*</span></label>
        <select name="store_id" class="form-control inv-select2" required>
            <option value="">— Select —</option>
            @foreach($stores as $store)
                <option value="{{ $store->id }}" @selected(old('store_id', $requisition->store_id ?? '') == $store->id)>{{ $store->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Requisition Date <span class="text-danger">*</span></label>
        <input type="date" name="requisition_date" class="form-control" value="{{ old('requisition_date', optional($requisition->requisition_date ?? null)->format('Y-m-d') ?? now()->toDateString()) }}" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Received By</label>
        <select name="received_by" class="form-control inv-select2">
            <option value="">— Select Employee —</option>
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}" @selected(old('received_by', $requisition->received_by ?? '') == $employee->id)>{{ $employee->name }} ({{ $employee->employee_id }})</option>
            @endforeach
        </select>
        <div class="form-text">The HR employee who will receive this material from the store.</div>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Requisition For</label>
        <select name="requisition_for" class="form-control inv-select2">
            <option value="">— None —</option>
            @foreach(['fabrics' => 'Fabrics', 'accessories' => 'Accessories', 'machine_parts' => 'MachineParts', 'equipment' => 'Equipment', 'stationery' => 'Stationery'] as $value => $label)
                <option value="{{ $value }}" @selected(old('requisition_for', $requisition->requisition_for ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Buyer</label>
        <select name="buyer_id" class="form-control inv-select2">
            <option value="">— None —</option>
            @foreach($buyers as $buyer)
                <option value="{{ $buyer->id }}" @selected(old('buyer_id', $requisition->buyer_id ?? '') == $buyer->id)>{{ $buyer->name }}</option>
            @endforeach
        </select>
        <div class="form-text">Which buyer's order this material is needed for.</div>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Style</label>
        <input type="text" name="style" class="form-control" value="{{ old('style', $requisition->style ?? '') }}" placeholder="e.g. Style-A">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Order Ref</label>
        <input type="text" name="order_ref" class="form-control" value="{{ old('order_ref', $requisition->order_ref ?? '') }}">
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $requisition->remarks ?? '') }}</textarea>
    </div>
</div>

<hr>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Items</h6>
    <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="req"><i class="fa-solid fa-plus"></i> Add Row</button>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
        <thead><tr><th style="min-width:220px">Item</th><th style="width:90px">Unit</th><th style="width:160px">Requested Qty</th><th style="width:40px"></th></tr></thead>
        <tbody id="reqRowsBody">
            @php $lines = old('items', isset($requisition) ? $requisition->items->map(fn ($i) => $i->toArray())->all() : [[]]); @endphp
            @foreach($lines as $index => $line)
                @php $selectedItem = $items->firstWhere('id', (int) ($line['item_id'] ?? null)); @endphp
                <tr>
                    <td>
                        <select name="items[{{ $index }}][item_id]" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" data-unit="{{ $item->unit?->short_name }}" data-store="{{ $item->opening_store_id }}" @selected(($line['item_id'] ?? null) == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="text" class="form-control" data-role="unit" value="{{ $selectedItem?->unit?->short_name }}" disabled></td>
                    <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][requested_qty]" class="form-control" value="{{ $line['requested_qty'] ?? '' }}" required></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<template id="reqRowTemplate">
    <tr>
        <td>
            <select name="items[__INDEX__][item_id]" class="form-control inv-select2" required>
                <option value="">— Select —</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}" data-unit="{{ $item->unit?->short_name }}" data-store="{{ $item->opening_store_id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" class="form-control" data-role="unit" disabled></td>
        <td><input type="number" step="0.0001" min="0.0001" name="items[__INDEX__][requested_qty]" class="form-control" required></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
    </tr>
</template>
