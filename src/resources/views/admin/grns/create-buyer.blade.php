@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add Buyer Supplied Challan') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Buyer Supplied Challan</h5>
            <a href="{{ route('inventory.grns.create') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.grns.store') }}">
                @csrf
                <input type="hidden" name="source_type" value="buyer_supplied">

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Store <span class="text-danger">*</span></label>
                        <select name="{{ $buyerStore ? '' : 'store_id' }}" class="form-control inv-select2" required @disabled($buyerStore)>
                            <option value="">— Select —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('store_id', $buyerStore?->id) == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                        @if($buyerStore)
                            <input type="hidden" name="store_id" value="{{ $buyerStore->id }}">
                        @endif
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Buyer <span class="text-danger">*</span></label>
                        <select name="buyer_id" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($buyers as $buyer)
                                <option value="{{ $buyer->id }}" @selected(old('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Style</label>
                        <input type="text" name="style" class="form-control" value="{{ old('style') }}" placeholder="e.g. Style-A">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Purchase order ref</label>
                        <input type="text" name="order_ref" class="form-control" value="{{ old('order_ref') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Receive Date <span class="text-danger">*</span></label>
                        <input type="date" name="receive_date" class="form-control" value="{{ old('receive_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Invoice / Challan No.</label>
                        <input type="text" name="challan_invoice_no" class="form-control" value="{{ old('challan_invoice_no') }}" placeholder="Buyer's challan number">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Received By</label>
                        <select name="received_by" class="form-control inv-select2">
                            <option value="">— Select —</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('received_by') == $employee->id)>{{ $employee->name }} ({{ $employee->employee_id }})</option>
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
                            <tr><th style="min-width:220px">Item</th><th style="width:90px">Unit</th><th>Received Qty</th><th></th></tr>
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
                                    <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][received_qty]" class="form-control" value="{{ $line['received_qty'] ?? '' }}" required></td>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][rate]" value="0">
                                        <input type="hidden" name="items[{{ $index }}][rejected_qty]" value="0">
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button>
                                    </td>
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
                        <td><input type="number" step="0.0001" min="0.0001" name="items[__INDEX__][received_qty]" class="form-control" required></td>
                        <td>
                            <input type="hidden" name="items[__INDEX__][rate]" value="0">
                            <input type="hidden" name="items[__INDEX__][rejected_qty]" value="0">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button>
                        </td>
                    </tr>
                </template>

                <button type="submit" class="btn btn-primary mt-3">Post Challan &amp; Update Stock</button>
                <a href="{{ route('inventory.grns.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@endsection
