@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add GRN') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Add GRN {{ $purchaseOrder ? '— against ' . $purchaseOrder->po_number : '' }}</h5>
            <a href="{{ route('inventory.grns.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.grns.store') }}">
                @csrf
                @if($purchaseOrder)
                    <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
                @endif

                <div class="row">
                    @unless($purchaseOrder)
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Order (optional)</label>
                            <select name="purchase_order_id" class="form-control inv-select2" id="grnPoSelect">
                                <option value="">— Direct GRN (no PO) —</option>
                                @foreach(\ME\SflInventory\Models\InvPurchaseOrder::whereIn('status', ['approved', 'received'])->orderByDesc('id')->get() as $po)
                                    <option value="{{ $po->id }}" @selected(old('purchase_order_id') == $po->id)>{{ $po->po_number }} — {{ $po->supplier?->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label d-block">Receipt Type <span class="text-danger">*</span></label>
                            <div id="grnSourceTypeToggle" class="border rounded p-2">
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="source_type" id="sourceTypePurchase" value="purchase" @checked(old('source_type', 'purchase') === 'purchase')>
                                    <label class="form-check-label" for="sourceTypePurchase"><strong>Purchase</strong> — received from a Supplier</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="source_type" id="sourceTypeBuyer" value="buyer_supplied" @checked(old('source_type') === 'buyer_supplied')>
                                    <label class="form-check-label" for="sourceTypeBuyer"><strong>Buyer Supplied</strong> — no purchase, no supplier</label>
                                </div>
                            </div>
                            <div class="form-text">Fabric/accessories the buyer sends directly go straight into the store here — pick "Buyer Supplied" to skip the supplier and enter the Buyer/Style instead.</div>
                        </div>
                    @else
                        <input type="hidden" name="source_type" value="purchase">
                    @endif
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Store <span class="text-danger">*</span></label>
                        <select name="store_id" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 grn-supplier-field">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-control inv-select2">
                            <option value="">— Select —</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchaseOrder->supplier_id ?? '') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 grn-buyer-field" style="display:none;">
                        <label class="form-label">Buyer <span class="text-danger">*</span></label>
                        <select name="buyer_id" class="form-control inv-select2">
                            <option value="">— Select —</option>
                            @foreach($buyers as $buyer)
                                <option value="{{ $buyer->id }}" @selected(old('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 grn-buyer-field" style="display:none;">
                        <label class="form-label">Style</label>
                        <input type="text" name="style" class="form-control" value="{{ old('style') }}" placeholder="e.g. Style-A">
                    </div>
                    <div class="col-md-3 mb-3 grn-buyer-field" style="display:none;">
                        <label class="form-label">Order Ref</label>
                        <input type="text" name="order_ref" class="form-control" value="{{ old('order_ref') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Receive Date <span class="text-danger">*</span></label>
                        <input type="date" name="receive_date" class="form-control" value="{{ old('receive_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Invoice / Challan No.</label>
                        <input type="text" name="challan_invoice_no" class="form-control" value="{{ old('challan_invoice_no') }}" placeholder="Supplier's invoice or challan number">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Received By</label>
                        <select name="received_by" class="form-control inv-select2">
                            <option value="">— Select —</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('received_by', auth()->id()) == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="grn"><i class="fa-solid fa-plus"></i> Add Row</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead>
                            <tr><th style="min-width:220px">Item</th><th>Ordered</th><th>Received Qty</th><th>Rejected Qty</th><th>Rate</th><th>Lot No</th><th>Batch No</th><th>Amount</th><th></th></tr>
                        </thead>
                        <tbody id="grnRowsBody">
                            @php $lines = old('items', $purchaseOrder ? $purchaseOrder->items->map(fn ($i) => ['purchase_order_item_id' => $i->id, 'item_id' => $i->item_id, 'ordered_qty' => $i->quantity - $i->received_qty, 'rate' => $i->rate])->all() : [[]]); @endphp
                            @foreach($lines as $index => $line)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][item_id]" class="form-control inv-select2" required @if(isset($line['purchase_order_item_id'])) disabled @endif>
                                            <option value="">— Select —</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" @selected(($line['item_id'] ?? null) == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                                            @endforeach
                                        </select>
                                        @if(isset($line['purchase_order_item_id']))
                                            <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $line['item_id'] }}">
                                            <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $line['purchase_order_item_id'] }}">
                                        @endif
                                    </td>
                                    <td><input type="text" class="form-control" value="{{ $line['ordered_qty'] ?? '' }}" disabled>
                                        <input type="hidden" name="items[{{ $index }}][ordered_qty]" value="{{ $line['ordered_qty'] ?? 0 }}"></td>
                                    <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][received_qty]" class="form-control" data-role="qty" value="{{ $line['ordered_qty'] ?? '' }}" required></td>
                                    <td><input type="number" step="0.0001" min="0" name="items[{{ $index }}][rejected_qty]" class="form-control" value="0"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][rate]" class="form-control" data-role="rate" value="{{ $line['rate'] ?? '' }}" required></td>
                                    <td><input type="text" name="items[{{ $index }}][lot_no]" class="form-control"></td>
                                    <td><input type="text" name="items[{{ $index }}][batch_no]" class="form-control"></td>
                                    <td><input type="text" class="form-control" data-role="amount" disabled></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <template id="grnRowTemplate">
                    <tr>
                        <td>
                            <select name="items[__INDEX__][item_id]" class="form-control inv-select2" required>
                                <option value="">— Select —</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>—</td>
                        <td><input type="number" step="0.0001" min="0.0001" name="items[__INDEX__][received_qty]" class="form-control" data-role="qty" required></td>
                        <td><input type="number" step="0.0001" min="0" name="items[__INDEX__][rejected_qty]" class="form-control" value="0"></td>
                        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][rate]" class="form-control" data-role="rate" required></td>
                        <td><input type="text" name="items[__INDEX__][lot_no]" class="form-control"></td>
                        <td><input type="text" name="items[__INDEX__][batch_no]" class="form-control"></td>
                        <td><input type="text" class="form-control" data-role="amount" disabled></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                    </tr>
                </template>

                <button type="submit" class="btn btn-primary mt-3">Post GRN &amp; Update Stock</button>
                <a href="{{ route('inventory.grns.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')

@push('js')
<script>
    (function () {
        const toggle = document.getElementById('grnSourceTypeToggle');
        if (!toggle) {
            return;
        }
        const supplierFields = document.querySelectorAll('.grn-supplier-field');
        const buyerFields = document.querySelectorAll('.grn-buyer-field');
        const supplierSelect = document.querySelector('select[name="supplier_id"]');
        const buyerSelect = document.querySelector('select[name="buyer_id"]');

        function apply() {
            const isBuyerSupplied = document.getElementById('sourceTypeBuyer').checked;
            supplierFields.forEach(el => el.style.display = isBuyerSupplied ? 'none' : '');
            buyerFields.forEach(el => el.style.display = isBuyerSupplied ? '' : 'none');
            if (supplierSelect) supplierSelect.required = !isBuyerSupplied;
            if (buyerSelect) buyerSelect.required = isBuyerSupplied;
        }

        toggle.addEventListener('change', apply);
        apply();
    })();
</script>
@endpush
@endsection
