@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Edit GRN ' . $grn->grn_number) }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Edit Purchase Challan — {{ $grn->grn_number }}{{ $grn->purchaseOrder ? ' — against ' . $grn->purchaseOrder->po_number : '' }}</h5>
            <a href="{{ route('inventory.grns.show', $grn) }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">Editing a posted GRN reverses its original stock entries and re-posts the corrected quantities. This is blocked if any of this GRN's stock has already been issued or used elsewhere.</div>

            <form method="POST" action="{{ route('inventory.grns.update', $grn) }}">
                @csrf @method('PUT')
                <input type="hidden" name="source_type" value="purchase">
                <input type="hidden" name="store_id" value="{{ $grn->store_id }}">
                <input type="hidden" name="supplier_id" value="{{ $grn->supplier_id }}">
                @if($grn->purchase_order_id)
                    <input type="hidden" name="purchase_order_id" value="{{ $grn->purchase_order_id }}">
                @endif

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Store</label>
                        <input type="text" class="form-control" value="{{ $grn->store?->name }}" disabled>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Supplier</label>
                        <input type="text" class="form-control" value="{{ $grn->supplier?->name }}" disabled>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Receive Date <span class="text-danger">*</span></label>
                        <input type="date" name="receive_date" class="form-control" value="{{ old('receive_date', optional($grn->receive_date)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Invoice / Challan No.</label>
                        <input type="text" name="challan_invoice_no" class="form-control" value="{{ old('challan_invoice_no', $grn->challan_invoice_no) }}" placeholder="Supplier's invoice or challan number">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Received By</label>
                        <select name="received_by" class="form-control inv-select2">
                            <option value="">— Select —</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('received_by', $grn->received_by) == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $grn->remarks) }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Items</h6>
                    @unless($grn->purchase_order_id)
                        <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="grn"><i class="fa-solid fa-plus"></i> Add Row</button>
                    @else
                        <span class="text-muted" style="font-size:12px;">This GRN's own lines against {{ $grn->purchaseOrder->po_number }} — new items must be received via a new GRN.</span>
                    @endunless
                </div>

                @if($grn->purchase_order_id)
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead>
                                <tr><th style="min-width:220px">Item</th><th>Due Qty</th><th>Received Qty</th></tr>
                            </thead>
                            <tbody>
                                @foreach($grn->items as $index => $grnItem)
                                    @php
                                        $poItem = $grnItem->purchaseOrderItem;
                                        $due = $poItem ? $poItem->quantity - ($poItem->received_qty - $grnItem->received_qty) : $grnItem->received_qty;
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $grnItem->item?->item_code }} — {{ $grnItem->item?->item_name }}
                                            <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $grnItem->item_id }}">
                                            <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $grnItem->purchase_order_item_id }}">
                                            <input type="hidden" name="items[{{ $index }}][ordered_qty]" value="{{ $due }}">
                                            <input type="hidden" name="items[{{ $index }}][rate]" value="{{ $grnItem->rate }}">
                                            <input type="hidden" name="items[{{ $index }}][rejected_qty]" value="{{ $grnItem->rejected_qty }}">
                                        </td>
                                        <td>{{ inv_qty($due) }}</td>
                                        <td><input type="number" step="0.0001" min="0.0001" max="{{ $due }}" name="items[{{ $index }}][received_qty]" class="form-control" value="{{ old('items.' . $index . '.received_qty', $grnItem->received_qty) }}" required></td>
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
                                @foreach(old('items', $grn->items->map(fn ($i) => ['item_id' => $i->item_id, 'received_qty' => (float) $i->received_qty, 'rejected_qty' => (float) $i->rejected_qty, 'rate' => (float) $i->rate, 'lot_no' => $i->lot_no, 'batch_no' => $i->batch_no])->all()) as $index => $line)
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
                @endif

                <button type="submit" class="btn btn-primary mt-3">Save Changes &amp; Re-post Stock</button>
                <a href="{{ route('inventory.grns.show', $grn) }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
