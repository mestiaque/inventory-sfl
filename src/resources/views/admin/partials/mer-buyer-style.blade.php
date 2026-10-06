{{--
    Buyer → Style → PO picker — all from Merchandising (Inventory keeps no
    buyer / style list of its own). Styles: the buyer's Merchandising styles
    plus styles Inventory already received before ("inv:<style no>",
    MerchandisingLink::legacyStyles()). Request side: Concerns\PicksMerchandisingStyle.

    props: pick = [
        'id'       => DOM id prefix (default 'mbs'),
        'buyer'    => selected Merchandising buyer id,
        'style'    => selected value (style id or "inv:<style no>"),
        'po'       => selected PO id,
        'required' => buyer + style required (default false),
        'with_po'  => show the PO select (default true),
    ]
    + merBuyersOptions, merStylesOptions, merLegacyStyles, merOrderPosOptions
      (MerchandisingLink::formOptions()).
--}}
@php
    $mbsId = $pick['id'] ?? 'mbs';
    $mbsRequired = (bool) ($pick['required'] ?? false);
    $mbsWithPo = $pick['with_po'] ?? true;
    $mbsBuyer = (string) old('msfl_buyer_id', $pick['buyer'] ?? '');
    $mbsStyle = (string) (old('msfl_style_id') ?? (old('style') ? \ME\SflInventory\Services\MerchandisingLink::LEGACY_STYLE . old('style') : null) ?? ($pick['style'] ?? ''));
    $mbsPo = (string) old('msfl_order_po_id', $pick['po'] ?? '');
    $mbsPrefix = \ME\SflInventory\Services\MerchandisingLink::LEGACY_STYLE;
@endphp
<div class="col-md-3 mb-3">
    <label class="form-label">Buyer @if($mbsRequired)<span class="text-danger">*</span>@endif</label>
    <select name="msfl_buyer_id" id="{{ $mbsId }}Buyer" class="form-control form-control-sm inv-select2" @required($mbsRequired)>
        <option value="">— {{ $mbsRequired ? 'Select buyer' : 'None' }} —</option>
        @foreach($merBuyersOptions as $b)
            <option value="{{ $b->id }}" @selected($mbsBuyer === (string) $b->id)>{{ $b->name }}</option>
        @endforeach
    </select>
    @if($merBuyersOptions->isEmpty())
        <span class="form-text text-danger">No approved buyer in Merchandising yet.</span>
    @endif
</div>
<div class="col-md-3 mb-3">
    <label class="form-label">Style @if($mbsRequired)<span class="text-danger">*</span>@endif</label>
    <select name="msfl_style_id" id="{{ $mbsId }}Style" class="form-control form-control-sm inv-select2" @required($mbsRequired)>
        <option value="">— {{ $mbsRequired ? 'Select style' : 'None' }} —</option>
        @foreach($merStylesOptions as $s)
            <option value="{{ $s->id }}" data-buyer="{{ $s->buyer_id }}" data-style-no="{{ $s->style_no }}" @selected($mbsStyle === (string) $s->id)>{{ $s->style_no }} — {{ $s->name }}</option>
        @endforeach
        @foreach($merLegacyStyles ?? [] as $s)
            <option value="{{ $mbsPrefix . $s->style_no }}" data-buyer="{{ $s->buyer_id }}" data-style-no="{{ $s->style_no }}" @selected($mbsStyle === $mbsPrefix . $s->style_no)>{{ $s->style_no }} (Inventory)</option>
        @endforeach
    </select>
    <span class="form-text">The buyer's styles (Merchandising → Master Data → Styles).</span>
</div>
@if($mbsWithPo)
    <div class="col-md-3 mb-3">
        <label class="form-label">Order (PO) <small class="text-muted">optional</small></label>
        <select name="msfl_order_po_id" id="{{ $mbsId }}Po" class="form-control form-control-sm inv-select2">
            <option value="">— All orders of the style —</option>
            @foreach($merOrderPosOptions as $po)
                <option value="{{ $po->id }}" data-style="{{ $po->style_id }}" @selected($mbsPo === (string) $po->id)>
                    {{ $po->po_no }}{{ $po->order ? ' — ' . $po->order->order_no : '' }}
                </option>
            @endforeach
        </select>
    </div>
@endif

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buyer = document.getElementById(@json($mbsId . 'Buyer'));
        const style = document.getElementById(@json($mbsId . 'Style'));
        const po = document.getElementById(@json($mbsId . 'Po'));
        const jq = typeof $ !== 'undefined' ? $ : null;
        const allStyles = Array.from(style.options).map(function (o) { return o.cloneNode(true); });
        const allPos = po ? Array.from(po.options).map(function (o) { return o.cloneNode(true); }) : [];

        // Select2 can't hide options, so rebuild each list from its full copy.
        function rebuild(select, all, attr, value) {
            const keep = select.value;
            select.innerHTML = '';
            all.forEach(function (o) {
                if (!o.value || (value && o.dataset[attr] === String(value))) { select.appendChild(o.cloneNode(true)); }
            });
            select.value = Array.from(select.options).some(function (o) { return o.value === keep; }) ? keep : '';
            if (jq) { jq(select).trigger('change.select2'); }
        }
        function onBuyer() { rebuild(style, allStyles, 'buyer', buyer.value); onStyle(); }
        function onStyle() { if (po) { rebuild(po, allPos, 'style', style.value); } }

        if (jq) { jq(buyer).on('change', onBuyer); jq(style).on('change', onStyle); }
        else { buyer.addEventListener('change', onBuyer); style.addEventListener('change', onStyle); }
        onBuyer();
    });
</script>
@endpush
