@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add Purchase Challan') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Purchase Challan {{ $purchaseOrder ? '— against ' . $purchaseOrder->po_number : '' }}</h5>
            <a href="{{ route('inventory.grns.create') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.grns.store') }}">
                @csrf
                <input type="hidden" name="source_type" value="purchase">
                @if($purchaseOrder)
                    <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
                @endif

                <div class="row">
                    @unless($purchaseOrder)
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purchase Order (optional)</label>
                            <select name="purchase_order_id" class="form-control inv-select2" id="grnPoPicker">
                                <option value="">— Direct Challan (no PO) —</option>
                                @foreach(\ME\SflInventory\Models\InvPurchaseOrder::selectableForGrn()->orderByDesc('id')->get() as $po)
                                    <option value="{{ $po->id }}" @selected(old('purchase_order_id') == $po->id)>{{ $po->po_number }} — {{ $po->supplier?->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Only Approved / Received purchase orders can be selected. Picking one loads its store, supplier and items automatically.</div>
                        </div>
                    @endunless
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Store <span class="text-danger">*</span></label>
                        <select name="{{ $accessoriesStore ? '' : 'store_id' }}" class="form-control inv-select2" required @disabled($accessoriesStore)>
                            <option value="">— Select —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('store_id', $accessoriesStore?->id) == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                        @if($accessoriesStore)
                            <input type="hidden" name="store_id" value="{{ $accessoriesStore->id }}">
                        @endif
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="{{ $purchaseOrder ? '' : 'supplier_id' }}" class="form-control inv-select2" required @disabled($purchaseOrder)>
                            <option value="">— Select —</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchaseOrder->supplier_id ?? '') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @if($purchaseOrder)
                            <input type="hidden" name="supplier_id" value="{{ $purchaseOrder->supplier_id }}">
                        @endif
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
                    @unless($purchaseOrder)
                        <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="grn"><i class="fa-solid fa-plus"></i> Add Row</button>
                    @else
                        <span class="text-muted" style="font-size:12px;">Receiving against {{ $purchaseOrder->po_number }} — only its ordered items can be received here.</span>
                    @endunless
                </div>
                @if($purchaseOrder)
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead>
                                <tr><th style="min-width:220px">Item</th><th>Due Qty</th><th>Received Qty</th></tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->items as $index => $line)
                                    @php $due = $line->quantity - $line->received_qty; @endphp
                                    <tr>
                                        <td>
                                            {{ $line->item?->item_code }} — {{ $line->item?->item_name }}
                                            <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $line->item_id }}">
                                            <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $line->id }}">
                                            <input type="hidden" name="items[{{ $index }}][ordered_qty]" value="{{ $due }}">
                                            <input type="hidden" name="items[{{ $index }}][rate]" value="{{ $line->rate }}">
                                            <input type="hidden" name="items[{{ $index }}][rejected_qty]" value="0">
                                        </td>
                                        <td>{{ inv_qty($due) }}</td>
                                        <td><input type="number" step="0.0001" min="0.0001" max="{{ $due }}" name="items[{{ $index }}][received_qty]" class="form-control" value="{{ old('items.' . $index . '.received_qty', $due) }}" required></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead>
                                <tr><th style="min-width:220px">Item</th><th style="width:90px">Unit</th><th>Received Qty</th><th>Rejected Qty</th><th>Rate</th><th>Lot No</th><th>Batch No</th><th>Amount</th><th></th></tr>
                            </thead>
                            <tbody id="grnRowsBody">
                                @foreach(old('items', [[]]) as $index => $line)
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
                                        <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][received_qty]" class="form-control" data-role="qty" value="{{ $line['received_qty'] ?? '' }}" required></td>
                                        <td><input type="number" step="0.0001" min="0" name="items[{{ $index }}][rejected_qty]" class="form-control" value="{{ $line['rejected_qty'] ?? 0 }}"></td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][rate]" class="form-control" data-role="rate" value="{{ $line['rate'] ?? '' }}" required></td>
                                        <td><input type="text" name="items[{{ $index }}][lot_no]" class="form-control" value="{{ $line['lot_no'] ?? '' }}"></td>
                                        <td><input type="text" name="items[{{ $index }}][batch_no]" class="form-control" value="{{ $line['batch_no'] ?? '' }}"></td>
                                        <td><input type="text" class="form-control" data-role="amount" disabled></td>
                                        <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @unless($purchaseOrder)
                    <template id="grnRowTemplate">
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
                            <td><input type="number" step="0.0001" min="0.0001" name="items[__INDEX__][received_qty]" class="form-control" data-role="qty" required></td>
                            <td><input type="number" step="0.0001" min="0" name="items[__INDEX__][rejected_qty]" class="form-control" value="0"></td>
                            <td><input type="number" step="0.01" min="0" name="items[__INDEX__][rate]" class="form-control" data-role="rate" required></td>
                            <td><input type="text" name="items[__INDEX__][lot_no]" class="form-control"></td>
                            <td><input type="text" name="items[__INDEX__][batch_no]" class="form-control"></td>
                            <td><input type="text" class="form-control" data-role="amount" disabled></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                        </tr>
                    </template>
                @endunless

                <button type="submit" class="btn btn-primary mt-3">Post Challan &amp; Update Stock</button>
                <a href="{{ route('inventory.grns.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')

@unless($purchaseOrder)
    @push('js')
    <script>
        (function () {
            const picker = document.getElementById('grnPoPicker');
            if (!picker) {
                return;
            }
            function navigate() {
                const url = new URL(@json(route('inventory.grns.create-purchase')));
                if (picker.value) {
                    url.searchParams.set('purchase_order_id', picker.value);
                }
                window.location.href = url.toString();
            }
            picker.addEventListener('change', navigate);
            $(picker).on('change', navigate);
        })();
    </script>
    @endpush
@endunless
@endsection
