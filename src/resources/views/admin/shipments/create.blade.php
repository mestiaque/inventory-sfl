@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add Shipment') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Add Shipment</h5>
            <a href="{{ route('inventory.shipments.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info">This records what's being shipped — no stock moves yet. Once saved, issue a <strong>Gate Pass</strong> against it to actually release the goods.</div>
            <form method="POST" action="{{ route('inventory.shipments.store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Buyer</label>
                        <select name="buyer_id" class="form-control inv-select2">
                            <option value="">— Select —</option>
                            @foreach($buyers as $buyer)
                                <option value="{{ $buyer->id }}" @selected(old('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Store <span class="text-danger">*</span></label>
                        <select name="{{ $fgStore ? '' : 'store_id' }}" class="form-control inv-select2" required @disabled($fgStore)>
                            <option value="">— Select —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('store_id', $fgStore?->id) == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                        @if($fgStore)
                            <input type="hidden" name="store_id" value="{{ $fgStore->id }}">
                        @endif
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Shipment Date <span class="text-danger">*</span></label>
                        <input type="date" name="shipment_date" class="form-control" value="{{ old('shipment_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Invoice No</label>
                        <input type="text" name="invoice_no" class="form-control" value="{{ old('invoice_no') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Packing List No</label>
                        <input type="text" name="packing_list_no" class="form-control" value="{{ old('packing_list_no') }}">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="shp"><i class="fa-solid fa-plus"></i> Add Row</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th style="min-width:220px">Item</th><th style="width:90px">Unit</th><th style="width:140px">Available</th><th style="width:160px">Quantity</th><th style="width:40px"></th></tr></thead>
                        <tbody id="shpRowsBody">
                            @foreach(old('items', [[]]) as $index => $line)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][item_id]" class="form-control inv-select2" required>
                                            <option value="">— Select —</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" data-unit="{{ $item->unit?->short_name }}" @selected(($line['item_id'] ?? null) == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control" data-role="unit" disabled></td>
                                    <td><input type="text" class="form-control" data-role="available" disabled></td>
                                    <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][quantity]" class="form-control" data-role="qty" value="{{ $line['quantity'] ?? '' }}" required></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <template id="shpRowTemplate">
                    <tr>
                        <td>
                            <select name="items[__INDEX__][item_id]" class="form-control inv-select2" required>
                                <option value="">— Select —</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-unit="{{ $item->unit?->short_name }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" class="form-control" data-role="unit" disabled></td>
                        <td><input type="text" class="form-control" data-role="available" disabled></td>
                        <td><input type="number" step="0.0001" min="0.0001" name="items[__INDEX__][quantity]" class="form-control" data-role="qty" required></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                    </tr>
                </template>

                <button type="submit" class="btn btn-primary mt-3">Save Shipment</button>
                <a href="{{ route('inventory.shipments.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')

@push('js')
<script>
    (function () {
        const stockMap = @json($stockMap);

        function availableFor(itemId, storeId) {
            if (!itemId || !storeId) {
                return null;
            }
            const forItem = stockMap[itemId];
            if (!forItem) {
                return 0;
            }
            const bal = forItem[storeId];
            return bal === undefined ? 0 : parseFloat(bal);
        }

        // The store field may be a live select, or locked to a single
        // Finished Goods store (disabled select + hidden input carrying
        // the real value) — read whichever actually has the value.
        function currentStoreId() {
            const hidden = document.querySelector('input[type="hidden"][name="store_id"]');
            if (hidden) {
                return hidden.value;
            }
            const select = document.querySelector('select[name="store_id"]');
            return select ? select.value : '';
        }

        function syncRow(row) {
            const itemSelect = row.querySelector('select[name$="[item_id]"]');
            const unitField = row.querySelector('[data-role="unit"]');
            const availableField = row.querySelector('[data-role="available"]');
            const qtyInput = row.querySelector('[data-role="qty"]');
            if (!itemSelect || !unitField || !availableField || !qtyInput) {
                return;
            }

            const option = itemSelect.options[itemSelect.selectedIndex];
            const unit = option?.getAttribute('data-unit') || '';
            unitField.value = unit;

            const itemId = itemSelect.value;
            const storeId = currentStoreId();
            const available = availableFor(itemId, storeId);

            if (available === null) {
                availableField.value = storeId ? '—' : 'Select store first';
                qtyInput.removeAttribute('max');
            } else {
                availableField.value = available + (unit ? ' ' + unit : '');
                qtyInput.setAttribute('max', available);
                if (parseFloat(qtyInput.value || 0) > available) {
                    qtyInput.value = available > 0 ? available : '';
                }
            }
        }

        function syncAllRows() {
            document.querySelectorAll('#shpRowsBody tr').forEach(syncRow);
        }

        document.addEventListener('change', function (e) {
            if (e.target.matches('select[name="store_id"]')) {
                syncAllRows();
            } else if (e.target.matches('#shpRowsBody select[name$="[item_id]"]')) {
                syncRow(e.target.closest('tr'));
            } else if (e.target.matches('#shpRowsBody [data-role="qty"]')) {
                const max = parseFloat(e.target.getAttribute('max'));
                if (!isNaN(max) && parseFloat(e.target.value || 0) > max) {
                    e.target.value = max;
                }
            }
        });

        // select2 fires jQuery events, not native change events.
        $(document).on('change', 'select[name="store_id"]', syncAllRows);
        $(document).on('change', '#shpRowsBody select[name$="[item_id]"]', function () {
            syncRow(this.closest('tr'));
        });

        // Newly added rows via "Add Row" need their own listeners bound too.
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1 && node.tagName === 'TR') {
                        syncRow(node);
                    }
                });
            });
        });
        const body = document.getElementById('shpRowsBody');
        if (body) {
            observer.observe(body, { childList: true });
        }

        syncAllRows();
    })();
</script>
@endpush
@endsection
