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
            <h4 class="mb-0">Purchase Challan{{ $purchaseOrder ? ' — against ' . $purchaseOrder->po_number : '' }}</h4>
            <a href="{{ route('inventory.grns.create') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            @unless($purchaseOrder)
                <label class="form-label">Store Order <span class="text-danger">*</span></label>
                <select id="poPicker" class="form-control form-control-sm inv-select2" style="max-width:480px;">
                    <option value="">— Select an Approved Store Order —</option>
                    @foreach($purchaseOrders as $po)
                        <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->supplier?->name ?? 'No Supplier' }}</option>
                    @endforeach
                </select>
                <div class="form-text">A Purchase Challan can only be raised against an approved Store Order. Picking one loads its items below.</div>
                @if($purchaseOrders->isEmpty())
                    <div class="alert alert-warning mt-3 mb-0">No Store Orders available to receive against right now — <a href="{{ route('inventory.purchase-orders.index') }}">check the Store Order list</a>.</div>
                @endif
            @else
                <form method="POST" action="{{ route('inventory.grns.store') }}">
                    @csrf
                    <input type="hidden" name="source_type" value="purchase">
                    <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Store <span class="text-danger">*</span></label>
                            <select name="{{ $accessoriesStore ? '' : 'store_id' }}" class="form-control form-control-sm inv-select2" required @disabled($accessoriesStore)>
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
                            <select name="supplier_id" class="form-control form-control-sm inv-select2" required>
                                <option value="">— Select —</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchaseOrder->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            @unless($purchaseOrder->supplier_id)
                                <div class="form-text">Not picked yet at Store Order stage — select the actual supplier for this challan now.</div>
                            @endunless
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Receive Date <span class="text-danger">*</span></label>
                            <input type="date" name="receive_date" class="form-control form-control-sm" value="{{ old('receive_date', now()->toDateString()) }}" required>
                        </div>
                        @if(($merStylesOptions ?? collect())->isNotEmpty())
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Merchandising Style</label>
                            <select name="mer_style_id" class="form-control form-control-sm inv-select2">
                                <option value="">— None —</option>
                                @foreach($merStylesOptions as $s)
                                    <option value="{{ $s->id }}" @selected(old('mer_style_id') == $s->id)>{{ $s->style_no }} — {{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        @if(($merSalesContractPosOptions ?? collect())->isNotEmpty())
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sales Contract PO</label>
                            <select name="mer_sales_contract_po_id" class="form-control form-control-sm inv-select2">
                                <option value="">— None —</option>
                                @foreach($merSalesContractPosOptions as $po)
                                    <option value="{{ $po->id }}" @selected(old('mer_sales_contract_po_id') == $po->id)>{{ $po->po_no }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        @if(($merBuyersOptions ?? collect())->isNotEmpty())
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Merchandising Buyer</label>
                            <select name="mer_buyer_id" class="form-control form-control-sm inv-select2">
                                <option value="">— None —</option>
                                @foreach($merBuyersOptions as $b)
                                    <option value="{{ $b->id }}" @selected(old('mer_buyer_id') == $b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Invoice / Challan No.</label>
                            <input type="text" name="challan_invoice_no" class="form-control form-control-sm" value="{{ old('challan_invoice_no') }}" placeholder="Supplier's invoice or challan number">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Received By</label>
                            <select name="received_by" class="form-control form-control-sm inv-select2">
                                <option value="">— Select —</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected(old('received_by') == $employee->id)>{{ $employee->name }} ({{ $employee->employee_id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control form-control-sm" rows="2">{{ old('remarks') }}</textarea>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Items</h6>
                        <span class="text-muted" style="font-size:12px;">Receiving against {{ $purchaseOrder->po_number }} — only its ordered items can be received here.</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead>
                                <tr><th style="min-width:220px">Item</th><th>Color</th><th>Size</th><th>Due Qty</th><th style="width:150px">Received Qty</th><th style="width:140px">Rate</th><th style="width:140px">Amount</th><th>Expiry Date</th></tr>
                            </thead>
                            <tbody id="grnRowsBody">
                                @foreach($purchaseOrder->items as $index => $line)
                                    @php $due = $line->quantity - $line->received_qty; @endphp
                                    <tr>
                                        <td>
                                            {{ $line->item?->item_code }} — {{ $line->item?->item_name }}
                                            <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $line->item_id }}">
                                            <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $line->id }}">
                                            <input type="hidden" name="items[{{ $index }}][ordered_qty]" value="{{ $due }}">
                                            <input type="hidden" name="items[{{ $index }}][rejected_qty]" value="0">
                                        </td>
                                        <td>{{ $line->color?->name ?? '—' }}</td>
                                        <td>{{ $line->size?->name ?? '—' }}</td>
                                        <td>{{ inv_qty($due) }}</td>
                                        <td><input type="number" step="0.0001" min="0.0001" max="{{ $due }}" name="items[{{ $index }}][received_qty]" class="form-control form-control-sm" data-role="qty" value="{{ old('items.' . $index . '.received_qty', $due) }}" required></td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][rate]" class="form-control form-control-sm" data-role="rate" value="{{ old('items.' . $index . '.rate') }}" placeholder="Per supplier invoice" required></td>
                                        <td><input type="text" class="form-control form-control-sm" data-role="amount" disabled></td>
                                        <td><input type="date" name="items[{{ $index }}][expiry_date]" class="form-control form-control-sm"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Auto-approve option temporarily disabled — every challan should go through
                         the normal single Receive Approval step.
                    @can('inv_grn.approve')
                        <div class="form-check mt-3">
                            <input type="checkbox" name="auto_approve" value="1" class="form-check-input" id="grnAutoApprove" @checked(old('auto_approve'))>
                            <label class="form-check-label" for="grnAutoApprove">Auto-approve this challan</label>
                            <div class="form-text">Skips the separate Receive Approval step — stock updates immediately.</div>
                        </div>
                    @endcan
                    --}}

                    <button type="submit" class="btn btn-primary mt-3 btn-sm">Submit Challan for Receive Approval</button>
                    <a href="{{ route('inventory.grns.create-purchase') }}" class="btn btn-light mt-3 btn-sm">Change Store Order</a>
                </form>
            @endunless
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@if($purchaseOrder)
    @include('sfl-inventory::admin.partials.line-items-script')
@else
    @push('js')
    <script>
        (function () {
            const picker = document.getElementById('poPicker');
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
@endif
@endsection
