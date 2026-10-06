{{--
    Buyer Store receive — who / which style / which order the goods belong to.
    props: grn (optional, edit), buyers (inventory), merLinked, merBuyersOptions,
           merStylesOptions, merLegacyStyles, merOrderPosOptions

    Merchandising installed (and a new or already-linked GRN): Buyer -> Style ->
    PO all come from Merchandising, picked once; the inventory buyer, style text
    and order ref are filled from them on save. Otherwise the original
    inventory-buyer + free-text style fields are used.
--}}
@php
    $grn = $grn ?? null;
    $linked = ($merLinked ?? false) && (! $grn || $grn->msfl_buyer_id);
    $legacyPrefix = \ME\SflInventory\Services\MerchandisingLink::LEGACY_STYLE;
    $pickedStyle = old('msfl_style_id')
        ?? (old('style') ? $legacyPrefix . old('style') : null)
        ?? ($grn?->msfl_style_id ?: ($grn?->style ? $legacyPrefix . $grn->style : ''));
@endphp

@if($linked)
    <div class="col-md-3 mb-3">
        <label class="form-label">Buyer <span class="text-danger">*</span> <small class="text-muted">(Merchandising)</small></label>
        @if($grn)
            <input type="text" class="form-control form-control-sm bg-light" value="{{ $merBuyersOptions->firstWhere('id', $grn->msfl_buyer_id)->name ?? $grn->buyer?->name }}" readonly>
            <input type="hidden" name="msfl_buyer_id" id="grnMerBuyer" value="{{ $grn->msfl_buyer_id }}">
        @else
            <select name="msfl_buyer_id" id="grnMerBuyer" class="form-control form-control-sm inv-select2" required>
                <option value="">— Select buyer —</option>
                @foreach($merBuyersOptions as $b)
                    <option value="{{ $b->id }}" @selected(old('msfl_buyer_id') == $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
            @if($merBuyersOptions->isEmpty())
                <span class="form-text text-danger">No approved buyer in Merchandising yet.</span>
            @endif
        @endif
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Style <span class="text-danger">*</span></label>
        <select name="msfl_style_id" id="grnMerStyle" class="form-control form-control-sm inv-select2" required>
            <option value="">— Select style —</option>
            @foreach($merStylesOptions as $s)
                <option value="{{ $s->id }}" data-buyer="{{ $s->buyer_id }}" @selected((string) $pickedStyle === (string) $s->id)>{{ $s->style_no }} — {{ $s->name }}</option>
            @endforeach
            {{-- Styles Inventory already received but Merchandising doesn't have yet. --}}
            @foreach($merLegacyStyles ?? [] as $s)
                <option value="{{ $legacyPrefix . $s->style_no }}" data-buyer="{{ $s->buyer_id }}" @selected((string) $pickedStyle === $legacyPrefix . $s->style_no)>{{ $s->style_no }} (Inventory)</option>
            @endforeach
        </select>
        <span class="form-text">Only the selected buyer's styles — Merchandising + earlier receives.</span>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Order (PO)</label>
        <select name="msfl_order_po_id" id="grnMerPo" class="form-control form-control-sm inv-select2">
            <option value="">— All orders of the style —</option>
            @foreach($merOrderPosOptions as $po)
                <option value="{{ $po->id }}" data-style="{{ $po->style_id }}" @selected(old('msfl_order_po_id', $grn->msfl_order_po_id ?? '') == $po->id)>
                    {{ $po->po_no }}{{ $po->order ? ' — ' . $po->order->order_no : '' }}
                </option>
            @endforeach
        </select>
        <span class="form-text">Order ref is filled from the PO.</span>
    </div>

    @push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buyer = document.getElementById('grnMerBuyer');
            const style = document.getElementById('grnMerStyle');
            const po = document.getElementById('grnMerPo');
            const jq = typeof $ !== 'undefined' ? $ : null;
            const allStyles = Array.from(style.options).map(function (o) { return o.cloneNode(true); });
            const allPos = Array.from(po.options).map(function (o) { return o.cloneNode(true); });

            // Select2 can't hide options, so rebuild each list from its full copy.
            function rebuild(select, all, attr, value) {
                const keep = select.value;
                select.innerHTML = '';
                all.forEach(function (o) {
                    if (!o.value || (value && o.dataset[attr] === String(value))) {
                        select.appendChild(o.cloneNode(true));
                    }
                });
                select.value = Array.from(select.options).some(function (o) { return o.value === keep; }) ? keep : '';
                if (jq) { jq(select).trigger('change.select2'); }
            }
            function onBuyer() { rebuild(style, allStyles, 'buyer', buyer.value); onStyle(); }
            function onStyle() { rebuild(po, allPos, 'style', style.value); }

            if (jq) {
                jq(buyer).on('change', onBuyer);
                jq(style).on('change', onStyle);
            } else {
                buyer.addEventListener('change', onBuyer);
                style.addEventListener('change', onStyle);
            }
            onBuyer();
        });
    </script>
    @endpush
@else
    @unless($grn)
        <div class="col-md-3 mb-3">
            <label class="form-label">Buyer <span class="text-danger">*</span></label>
            <select name="buyer_id" class="form-control form-control-sm inv-select2" required>
                <option value="">— Select —</option>
                @foreach($buyers as $buyer)
                    <option value="{{ $buyer->id }}" @selected(old('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                @endforeach
            </select>
        </div>
    @endunless
    <div class="col-md-3 mb-3">
        <label class="form-label">Style</label>
        <input type="text" name="style" class="form-control form-control-sm" value="{{ old('style', $grn->style ?? '') }}" placeholder="e.g. Style-A">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Purchase order ref</label>
        <input type="text" name="order_ref" class="form-control form-control-sm" value="{{ old('order_ref', $grn->order_ref ?? '') }}">
    </div>
    @if($grn)
        {{-- Older, unlinked receipt: keep whatever Merchandising links it already had. --}}
        <input type="hidden" name="msfl_style_id" value="{{ $grn->msfl_style_id }}">
        <input type="hidden" name="msfl_order_po_id" value="{{ $grn->msfl_order_po_id }}">
        <input type="hidden" name="msfl_buyer_id" value="{{ $grn->msfl_buyer_id }}">
    @endif
@endif
