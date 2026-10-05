{{-- props: requisition (optional, for edit), departments, stores, items, buyers, employees --}}
<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Department / Section <span class="text-danger">*</span></label>
        <select name="department_id" class="form-control form-control-sm inv-select2" required>
            <option value="">— Select —</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $requisition->department_id ?? '') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Issue From Store <span class="text-danger">*</span></label>
        <select name="store_id" id="reqStore" class="form-control form-control-sm inv-select2" required>
            <option value="">— Select —</option>
            @foreach($stores as $store)
                <option value="{{ $store->id }}" data-type="{{ $store->type }}" @selected(old('store_id', $requisition->store_id ?? '') == $store->id)>{{ $store->name }} ({{ $store->typeLabel() }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Requisition Date <span class="text-danger">*</span></label>
        <input type="date" name="requisition_date" class="form-control form-control-sm" value="{{ old('requisition_date', optional($requisition->requisition_date ?? null)->format('Y-m-d') ?? now()->toDateString()) }}" required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Received By</label>
        <select name="received_by" class="form-control form-control-sm inv-select2">
            <option value="">— Select Employee —</option>
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}" @selected(old('received_by', $requisition->received_by ?? '') == $employee->id)>{{ $employee->name }} ({{ $employee->employee_id }})</option>
            @endforeach
        </select>
        <div class="form-text">The HR employee who will receive this material from the store.</div>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Requisition For</label>
        <select name="requisition_for" class="form-control form-control-sm inv-select2">
            <option value="">— None —</option>
            @php
                $purposeOptions = $purposes->pluck('name', 'code');
                // Keep an edited requisition's option even if it's been made inactive since.
                if (($requisition->requisition_for ?? null) && ! $purposeOptions->has($requisition->requisition_for)) {
                    $purposeOptions->put($requisition->requisition_for, $requisition->purpose?->name ?? ucwords(str_replace('_', ' ', $requisition->requisition_for)));
                }
            @endphp
            @foreach($purposeOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('requisition_for', $requisition->requisition_for ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @php $req = $requisition ?? null; @endphp
    @if(($merLinked ?? false) && (! $req || $req->msfl_buyer_id || $req->msfl_style_id))
        {{-- Buyer / Style / PO from Merchandising. Required for the Buyer Store (PO optional), optional for the General Store. --}}
        <div class="col-md-3 mb-3">
            <label class="form-label">Buyer <span class="text-danger req-buyer-star">*</span> <small class="text-muted">(Merchandising)</small></label>
            <select name="msfl_buyer_id" id="reqMerBuyer" class="form-control form-control-sm inv-select2">
                <option value="">— None —</option>
                @foreach($merBuyersOptions as $b)
                    <option value="{{ $b->id }}" @selected(old('msfl_buyer_id', $req->msfl_buyer_id ?? '') == $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Style <span class="text-danger req-buyer-star">*</span></label>
            <select name="msfl_style_id" id="reqMerStyle" class="form-control form-control-sm inv-select2">
                <option value="">— None —</option>
                @foreach($merStylesOptions as $s)
                    <option value="{{ $s->id }}" data-buyer="{{ $s->buyer_id }}" data-style-no="{{ $s->style_no }}" data-received="{{ in_array($s->id, $merReceivedStyleIds ?? [], true) ? 1 : 0 }}"
                        @selected(old('msfl_style_id', $req->msfl_style_id ?? '') == $s->id)>{{ $s->style_no }} — {{ $s->name }}</option>
                @endforeach
            </select>
            <div class="form-text" id="reqStyleHint"></div>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Order (PO) <small class="text-muted">optional</small></label>
            <select name="msfl_order_po_id" id="reqMerPo" class="form-control form-control-sm inv-select2">
                <option value="">— All orders of the style —</option>
                @foreach($merOrderPosOptions as $po)
                    <option value="{{ $po->id }}" data-style="{{ $po->style_id }}" @selected(old('msfl_order_po_id', $req->msfl_order_po_id ?? '') == $po->id)>
                        {{ $po->po_no }}{{ $po->order ? ' — ' . $po->order->order_no : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const store = document.getElementById('reqStore');
                const buyer = document.getElementById('reqMerBuyer');
                const style = document.getElementById('reqMerStyle');
                const po = document.getElementById('reqMerPo');
                const hint = document.getElementById('reqStyleHint');
                const jq = typeof $ !== 'undefined' ? $ : null;
                const allStyles = Array.from(style.options).map(function (o) { return o.cloneNode(true); });
                const allPos = Array.from(po.options).map(function (o) { return o.cloneNode(true); });

                function fromBuyerStore() {
                    const opt = store.options[store.selectedIndex];
                    return !!opt && opt.dataset.type === @json(\ME\SflInventory\Models\InvStore::TYPE_BUYER);
                }
                // Select2 can't hide options, so rebuild each list from its full copy.
                function rebuild(select, all, keepFn) {
                    const keep = select.value;
                    select.innerHTML = '';
                    all.forEach(function (o) { if (!o.value || keepFn(o)) { select.appendChild(o.cloneNode(true)); } });
                    select.value = Array.from(select.options).some(function (o) { return o.value === keep; }) ? keep : '';
                    if (jq) { jq(select).trigger('change.select2'); }
                }
                function refresh() {
                    const buyerStore = fromBuyerStore();
                    buyer.required = buyerStore;
                    style.required = buyerStore;
                    document.querySelectorAll('.req-buyer-star').forEach(function (el) { el.style.display = buyerStore ? '' : 'none'; });
                    // Buyer Store: only styles that have actually been received there.
                    rebuild(style, allStyles, function (o) {
                        return buyer.value && o.dataset.buyer === buyer.value && (!buyerStore || o.dataset.received === '1');
                    });
                    hint.textContent = buyerStore ? 'Only styles received into the Buyer Store.' : 'Optional for the General Store.';
                    refreshPo();
                }
                function refreshPo() {
                    rebuild(po, allPos, function (o) { return style.value && o.dataset.style === style.value; });
                }

                if (jq) {
                    jq(store).on('change', refresh);
                    jq(buyer).on('change', refresh);
                    jq(style).on('change', refreshPo);
                } else {
                    store.addEventListener('change', refresh);
                    buyer.addEventListener('change', refresh);
                    style.addEventListener('change', refreshPo);
                }
                refresh();
            });
        </script>
        @endpush
    @else
        <div class="col-md-3 mb-3">
            <label class="form-label">Buyer</label>
            <select name="buyer_id" class="form-control form-control-sm inv-select2">
                <option value="">— None —</option>
                @foreach($buyers as $buyer)
                    <option value="{{ $buyer->id }}" @selected(old('buyer_id', $req->buyer_id ?? '') == $buyer->id)>{{ $buyer->name }}</option>
                @endforeach
            </select>
            <div class="form-text">Which buyer's order this material is needed for.</div>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Style</label>
            <input type="text" name="style" class="form-control form-control-sm" value="{{ old('style', $req->style ?? '') }}" placeholder="e.g. Style-A">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Order Ref</label>
            <input type="text" name="order_ref" class="form-control form-control-sm" value="{{ old('order_ref', $req->order_ref ?? '') }}">
        </div>
        @if($req)
            {{-- Older, unlinked requisition: keep whatever Merchandising links it already had. --}}
            <input type="hidden" name="msfl_style_id" value="{{ $req->msfl_style_id }}">
            <input type="hidden" name="msfl_order_po_id" value="{{ $req->msfl_order_po_id }}">
            <input type="hidden" name="msfl_buyer_id" value="{{ $req->msfl_buyer_id }}">
        @endif
    @endif
    <div class="col-12 mb-3">
        <label class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control form-control-sm" rows="2">{{ old('remarks', $requisition->remarks ?? '') }}</textarea>
    </div>
</div>

<hr>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Items</h6>
    <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="req"><i class="fa-solid fa-plus"></i> Add Row</button>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
        <thead><tr><th style="min-width:220px">Item</th><th style="width:90px">Unit</th><th style="width:140px">Color</th><th style="width:140px">Size</th><th style="width:150px">Stock</th><th style="width:160px">Requested Qty</th><th style="width:40px"></th></tr></thead>
        <tbody id="reqRowsBody">
            @php $lines = old('items', isset($requisition) ? $requisition->items->map(fn ($i) => $i->toArray())->all() : [[]]); @endphp
            @foreach($lines as $index => $line)
                @php $selectedItem = $items->firstWhere('id', (int) ($line['item_id'] ?? null)); @endphp
                <tr>
                    <td>
                        <select name="items[{{ $index }}][item_id]" class="form-control form-control-sm inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" data-unit="{{ $item->unit?->short_name }}" data-store="{{ $item->opening_store_id }}" data-color-id="{{ $item->color_id }}" data-size-id="{{ $item->size_id }}" @selected(($line['item_id'] ?? null) == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
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
                    <td data-role="stock" data-live-style @if(isset($requisition)) data-exclude-requisition="{{ $requisition->id }}" @endif></td>
                    <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][requested_qty]" class="form-control form-control-sm" value="{{ $line['requested_qty'] ?? '' }}" data-stock-qty="available" required></td>
                    <td><button type="button" class="btn-custom danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<template id="reqRowTemplate">
    <tr>
        <td>
            <select name="items[__INDEX__][item_id]" class="form-control form-control-sm inv-select2" required>
                <option value="">— Select —</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}" data-unit="{{ $item->unit?->short_name }}" data-store="{{ $item->opening_store_id }}" data-color-id="{{ $item->color_id }}" data-size-id="{{ $item->size_id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
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
        <td data-role="stock" data-live-style @if(isset($requisition)) data-exclude-requisition="{{ $requisition->id }}" @endif></td>
        <td><input type="number" step="0.0001" min="0.0001" name="items[__INDEX__][requested_qty]" class="form-control form-control-sm" data-stock-qty="available" required></td>
        <td><button type="button" class="btn-custom danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
    </tr>
</template>

@push('js')
<script>
    // Buyer Store stock belongs to a buyer + style: once a buyer (and style)
    // is picked, the item lists only show what that buyer / style actually
    // received (posted GRNs). Same style identity as StockService::styleBalance().
    // Applied by line-items-script on top of its own store filter.
    (function () {
        const rowsByItem = {};
        @json($styleItemRows ?? []).forEach(function (r) { (rowsByItem[r.i] = rowsByItem[r.i] || []).push(r); });
        const merBuyerInvIds = @json((object) ($merBuyerInvIds ?? []));
        const buyerType = @json(\ME\SflInventory\Models\InvStore::TYPE_BUYER);
        const field = function (name) { return document.querySelector('[name="' + name + '"]'); };

        function fromBuyerStore() {
            const store = document.getElementById('reqStore');
            const opt = store && store.options[store.selectedIndex];
            return !!opt && opt.dataset.type === buyerType;
        }

        function context() {
            const merBuyer = field('msfl_buyer_id');
            const merStyle = field('msfl_style_id');
            if (merBuyer && merBuyer.tagName === 'SELECT') {
                const styleOpt = merStyle && merStyle.options[merStyle.selectedIndex];
                return {
                    picked: !!merBuyer.value || !!merStyle.value,
                    buyer: merBuyer.value ? (merBuyerInvIds[merBuyer.value] || null) : null,
                    mer: merStyle.value ? parseInt(merStyle.value, 10) : null,
                    style: merStyle.value && styleOpt ? (styleOpt.dataset.styleNo || '').trim().toLowerCase() : '',
                };
            }
            const buyer = field('buyer_id');
            const style = field('style');
            const buyerId = buyer && buyer.value ? parseInt(buyer.value, 10) : null;
            const styleText = style ? style.value.trim().toLowerCase() : '';
            return { picked: !!buyerId || !!styleText, buyer: buyerId, mer: null, style: styleText };
        }

        function matches(r, ctx) {
            if (ctx.mer || ctx.style) {
                if (ctx.mer && r.m === ctx.mer) { return true; }
                if (ctx.mer && r.m !== null) { return false; }
                return r.b === ctx.buyer && r.s === ctx.style;
            }
            return r.b === ctx.buyer;
        }

        window.invItemOptionFilter = function (opt) {
            if (!fromBuyerStore()) { return true; }
            const ctx = context();
            if (!ctx.picked) { return true; }
            return (rowsByItem[opt.value] || []).some(function (r) { return matches(r, ctx); });
        };

        $(document).on('change', '[name="buyer_id"], [name="style"], [name="msfl_buyer_id"], [name="msfl_style_id"]', function () {
            // After the Merchandising script has rebuilt the Style list for a new buyer.
            setTimeout(function () { if (window.invRefilterLineItems) { window.invRefilterLineItems(); } }, 0);
        });
    })();
</script>
@endpush

@include('sfl-inventory::admin.partials.stock-hint-script')
