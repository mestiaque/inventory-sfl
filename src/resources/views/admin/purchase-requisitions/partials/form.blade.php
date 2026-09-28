{{-- props: purchaseRequisition (optional, for edit), departments, items, colors, sizes --}}
<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Department / Section</label>
        <select name="department_id" class="form-control form-control-sm inv-select2">
            <option value="">— None —</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $purchaseRequisition->department_id ?? '') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Requisition Date <span class="text-danger">*</span></label>
        <input type="date" name="requisition_date" class="form-control form-control-sm" value="{{ old('requisition_date', optional($purchaseRequisition->requisition_date ?? null)->format('Y-m-d') ?? now()->toDateString()) }}" required>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control form-control-sm" rows="2">{{ old('remarks', $purchaseRequisition->remarks ?? '') }}</textarea>
    </div>
</div>

<hr>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Items</h6>
    <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="preq"><i class="fa-solid fa-plus"></i> Add Row</button>
</div>
<p class="text-muted" style="font-size:12px;">Color/Size only need to be picked for an item that has no fixed color or size of its own on the Item Master.</p>
<div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
        <thead><tr><th style="min-width:220px">Item</th><th style="width:90px">Unit</th><th style="width:140px">Color</th><th style="width:140px">Size</th><th style="width:140px">Requested Qty</th><th style="width:150px">Estimated Rate <span class="text-danger">*</span><br><small class="text-muted font-weight-normal">market price / unit</small></th><th style="width:140px">Est. Amount</th><th style="width:40px"></th></tr></thead>
        <tbody id="preqRowsBody">
            @php $lines = old('items', isset($purchaseRequisition) ? $purchaseRequisition->items->map(fn ($i) => $i->toArray())->all() : [[]]); @endphp
            @foreach($lines as $index => $line)
                @php $selectedItem = $items->firstWhere('id', (int) ($line['item_id'] ?? null)); @endphp
                <tr>
                    <td>
                        <select name="items[{{ $index }}][item_id]" class="form-control form-control-sm inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" data-unit="{{ $item->unit?->short_name }}" data-color-id="{{ $item->color_id }}" data-size-id="{{ $item->size_id }}" @selected(($line['item_id'] ?? null) == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="text" class="form-control form-control-sm" data-role="unit" value="{{ $selectedItem?->unit?->short_name }}" disabled></td>
                    <td>
                        <select name="items[{{ $index }}][color_id]" class="form-control form-control-sm">
                            <option value="">— Select —</option>
                            @foreach($colors as $color)
                                <option value="{{ $color->id }}" @selected(($line['color_id'] ?? null) == $color->id)>{{ $color->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="items[{{ $index }}][size_id]" class="form-control form-control-sm">
                            <option value="">— Select —</option>
                            @foreach($sizes as $size)
                                <option value="{{ $size->id }}" @selected(($line['size_id'] ?? null) == $size->id)>{{ $size->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][requested_qty]" class="form-control form-control-sm" data-role="qty" value="{{ $line['requested_qty'] ?? '' }}" required></td>
                    <td><input type="number" step="0.01" min="0.01" name="items[{{ $index }}][estimated_rate]" class="form-control form-control-sm" data-role="rate" value="{{ $line['estimated_rate'] ?? '' }}" placeholder="Tk" required></td>
                    <td><input type="text" class="form-control form-control-sm text-right" data-role="amount" value="{{ isset($line['requested_qty'], $line['estimated_rate']) && $line['estimated_rate'] !== '' ? number_format((float) $line['requested_qty'] * (float) $line['estimated_rate'], 2, '.', '') : '' }}" readonly tabindex="-1"></td>
                    <td><button type="button" class="btn-custom danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-weight-bold">
                <td colspan="6" class="text-right">Estimated Total (Tk)</td>
                <td><input type="text" id="preqEstimatedTotal" class="form-control form-control-sm text-right font-weight-bold" readonly tabindex="-1"></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<template id="preqRowTemplate">
    <tr>
        <td>
            <select name="items[__INDEX__][item_id]" class="form-control form-control-sm inv-select2" required>
                <option value="">— Select —</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}" data-unit="{{ $item->unit?->short_name }}" data-color-id="{{ $item->color_id }}" data-size-id="{{ $item->size_id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm" data-role="unit" disabled></td>
        <td>
            <select name="items[__INDEX__][color_id]" class="form-control form-control-sm">
                <option value="">— Select —</option>
                @foreach($colors as $color)
                    <option value="{{ $color->id }}">{{ $color->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="items[__INDEX__][size_id]" class="form-control form-control-sm">
                <option value="">— Select —</option>
                @foreach($sizes as $size)
                    <option value="{{ $size->id }}">{{ $size->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.0001" min="0.0001" name="items[__INDEX__][requested_qty]" class="form-control form-control-sm" data-role="qty" required></td>
        <td><input type="number" step="0.01" min="0.01" name="items[__INDEX__][estimated_rate]" class="form-control form-control-sm" data-role="rate" placeholder="Tk" required></td>
        <td><input type="text" class="form-control form-control-sm text-right" data-role="amount" readonly tabindex="-1"></td>
        <td><button type="button" class="btn-custom danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
    </tr>
</template>

@push('js')
<script>
    // Estimated Total = Σ line amounts (each line's qty × rate is filled in by line-items-script).
    (function () {
        const body = document.getElementById('preqRowsBody');
        const total = document.getElementById('preqEstimatedTotal');
        function refresh() {
            let sum = 0;
            body.querySelectorAll('tr').forEach(function (row) {
                sum += parseFloat(row.querySelector('[data-role="qty"]')?.value || 0) * parseFloat(row.querySelector('[data-role="rate"]')?.value || 0);
            });
            total.value = sum.toFixed(2);
        }
        body.addEventListener('input', refresh);
        body.addEventListener('click', function () { setTimeout(refresh, 0); });
        document.querySelector('[data-line-items-add="preq"]')?.addEventListener('click', function () { setTimeout(refresh, 0); });
        refresh();
    })();
</script>
@endpush
