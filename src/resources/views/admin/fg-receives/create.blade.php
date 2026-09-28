@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add FG Receive') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Add Finished Goods Receive</h4>
            <a href="{{ route('inventory.fg-receives.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.fg-receives.store') }}">
                @csrf
                <div class="row">
                    @if($merLinked ?? false)
                        {{-- Buyer + Style required, PO optional — all from Merchandising; checked against production's packed qty. --}}
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Buyer <span class="text-danger">*</span> <small class="text-muted">(Merchandising)</small></label>
                            <select name="mer_buyer_id" id="fgrBuyer" class="form-control form-control-sm inv-select2" required>
                                <option value="">— Select buyer —</option>
                                @foreach($merBuyersOptions as $b)
                                    <option value="{{ $b->id }}" @selected(old('mer_buyer_id') == $b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Style <span class="text-danger">*</span></label>
                            <select name="mer_style_id" id="fgrStyle" class="form-control form-control-sm inv-select2" required>
                                <option value="">— Select style —</option>
                                @foreach($merStylesOptions as $s)
                                    <option value="{{ $s->id }}" data-buyer="{{ $s->buyer_id }}" @selected(old('mer_style_id') == $s->id)>{{ $s->style_no }} — {{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Order (PO) <small class="text-muted">optional</small></label>
                            <select name="mer_sales_contract_po_id" id="fgrPo" class="form-control form-control-sm inv-select2">
                                <option value="">— All orders of the style —</option>
                                @foreach($merSalesContractPosOptions as $po)
                                    <option value="{{ $po->id }}" data-style="{{ $po->style_id }}" @selected(old('mer_sales_contract_po_id') == $po->id)>
                                        {{ $po->po_no }}{{ $po->salesContract ? ' — ' . $po->salesContract->contract_no : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mb-3" id="fgrSummary" style="display:none;">
                            <table class="table table-bordered table-sm mb-1" style="max-width:720px;">
                                <thead>
                                    <tr><th>Order Qty</th><th>Packed by Production</th><th>Already in Finish Store</th><th>Can Receive Now</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-right" data-fgr="order_qty">-</td>
                                        <td class="text-right" data-fgr="packed">-</td>
                                        <td class="text-right" data-fgr="received">-</td>
                                        <td class="text-right font-weight-bold" data-fgr="remaining">-</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="small text-muted" id="fgrNote"></div>
                        </div>
                    @else
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Style</label>
                            <input type="text" name="style" class="form-control form-control-sm" value="{{ old('style', request('style')) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Buyer</label>
                            <select name="buyer_id" class="form-control form-control-sm inv-select2">
                                <option value="">— Select —</option>
                                @foreach($buyers as $buyer)
                                    <option value="{{ $buyer->id }}" @selected(old('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Order Ref</label>
                            <input type="text" name="order_ref" class="form-control form-control-sm" value="{{ old('order_ref', request('order_ref')) }}">
                        </div>
                    @endif
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Finished Goods Store <span class="text-danger">*</span></label>
                        <select name="{{ $fgStore ? '' : 'store_id' }}" class="form-control form-control-sm inv-select2" required @disabled($fgStore)>
                            <option value="">— Select —</option>
                            @foreach($stores->where('type', \ME\SflInventory\Models\InvStore::TYPE_FINISH) as $store)
                                <option value="{{ $store->id }}" @selected(old('store_id', $fgStore?->id) == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                        @if($fgStore)
                            <input type="hidden" name="store_id" value="{{ $fgStore->id }}">
                        @endif
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Receive Date <span class="text-danger">*</span></label>
                        <input type="date" name="receive_date" class="form-control form-control-sm" value="{{ old('receive_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="fgr"><i class="fa-solid fa-plus"></i> Add Row</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th style="min-width:220px">Finished Good Item</th><th style="width:160px">Quantity</th><th style="width:40px"></th></tr></thead>
                        <tbody id="fgrRowsBody">
                            <tr>
                                <td>
                                    <select name="items[0][item_id]" class="form-control form-control-sm inv-select2" required>
                                        <option value="">— Select —</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.0001" min="0.0001" name="items[0][quantity]" class="form-control form-control-sm" required></td>
                                <td><button type="button" class="btn-custom danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <template id="fgrRowTemplate">
                    <tr>
                        <td>
                            <select name="items[__INDEX__][item_id]" class="form-control form-control-sm inv-select2" required>
                                <option value="">— Select —</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.0001" min="0.0001" name="items[__INDEX__][quantity]" class="form-control form-control-sm" required></td>
                        <td><button type="button" class="btn-custom danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                    </tr>
                </template>

                <button type="submit" class="btn btn-primary mt-3 btn-sm">Post Receive &amp; Update Stock</button>
                <a href="{{ route('inventory.fg-receives.index') }}" class="btn btn-light mt-3 btn-sm">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@if($merLinked ?? false)
@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buyer = document.getElementById('fgrBuyer');
        const style = document.getElementById('fgrStyle');
        const po = document.getElementById('fgrPo');
        const box = document.getElementById('fgrSummary');
        const note = document.getElementById('fgrNote');
        const jq = typeof $ !== 'undefined' ? $ : null;
        const allStyles = Array.from(style.options).map(function (o) { return o.cloneNode(true); });
        const allPos = Array.from(po.options).map(function (o) { return o.cloneNode(true); });
        const fmt = function (v) { return v === null || v === undefined ? '—' : Number(v).toLocaleString(); };

        // Select2 can't hide options, so rebuild each list from its full copy.
        function rebuild(select, all, keepFn) {
            const keep = select.value;
            select.innerHTML = '';
            all.forEach(function (o) { if (!o.value || keepFn(o)) { select.appendChild(o.cloneNode(true)); } });
            select.value = Array.from(select.options).some(function (o) { return o.value === keep; }) ? keep : '';
            if (jq) { jq(select).trigger('change.select2'); }
        }
        function onBuyer() { rebuild(style, allStyles, function (o) { return buyer.value && o.dataset.buyer === buyer.value; }); onStyle(); }
        function onStyle() { rebuild(po, allPos, function (o) { return style.value && o.dataset.style === style.value; }); loadSummary(); }

        // Production packed vs already received; pre-fill the quantity with what's left.
        function loadSummary() {
            if (!style.value) { box.style.display = 'none'; return; }
            const url = @json(route('inventory.fg-receives.production-summary')) + '?style_id=' + style.value + (po.value ? '&po_id=' + po.value : '');
            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
                .then(function (d) {
                    box.style.display = '';
                    ['order_qty', 'packed', 'received', 'remaining'].forEach(function (k) {
                        box.querySelector('[data-fgr="' + k + '"]').textContent = fmt(d[k]);
                    });
                    if (d.packed === null) {
                        note.textContent = 'Production has no record for ' + (d.scope === 'po' ? 'this PO' : 'this style') + ' yet — quantity is not checked.';
                        return;
                    }
                    note.textContent = (d.scope === 'po' ? 'For this PO' : 'For all orders of this style') + ' · production figures last synced ' + (d.synced_at || '—') + ' (re-checked on save).';
                    const rows = document.querySelectorAll('#fgrRowsBody [name$="[quantity]"]');
                    if (rows.length === 1 && !rows[0].value && d.remaining > 0) { rows[0].value = d.remaining; }
                })
                .catch(function () { box.style.display = 'none'; });
        }

        if (jq) {
            jq(buyer).on('change', onBuyer);
            jq(style).on('change', onStyle);
            jq(po).on('change', loadSummary);
        } else {
            buyer.addEventListener('change', onBuyer);
            style.addEventListener('change', onStyle);
            po.addEventListener('change', loadSummary);
        }
        onBuyer();
    });
</script>
@endpush
@endif
@endsection
