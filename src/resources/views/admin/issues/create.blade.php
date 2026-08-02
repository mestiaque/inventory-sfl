@extends(adminTheme() . 'layouts.app')

@section('title')
    <title>{{ websiteTitle('Add Issue') }}</title>
@endsection

@section('contents')
<div class="flex-grow-1 inv-module">
    @include('sfl-inventory::admin.partials.alerts')
    @include('sfl-inventory::admin.partials.ui-kit')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Store Issue {{ $requisition ? '— against ' . $requisition->requisition_no : '(Direct Issue)' }}</h5>
            <a href="{{ route('inventory.issues.index') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.issues.store') }}">
                @csrf
                @if($requisition)
                    <input type="hidden" name="requisition_id" value="{{ $requisition->id }}">
                @endif

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" data-default-store="{{ $department->default_store_id }}"
                                    @selected(old('department_id', $requisition->department_id ?? '') == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Issue From Store <span class="text-danger">*</span></label>
                        <select name="store_id" class="form-control inv-select2" required>
                            <option value="">— Select —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('store_id', $requisition->store_id ?? '') == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Issue To Store (optional)</label>
                        <select name="to_store_id" id="toStoreSelect" class="form-control inv-select2">
                            <option value="">— None (consumed on issue) —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('to_store_id') == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">e.g. Cutting department → Cutting Store, so Production Consumption has a balance.</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', now()->toDateString()) }}" required>
                    </div>
                    @if($requisition)
                        <div class="col-12 mb-3">
                            <div class="alert alert-info mb-0 py-2">
                                <strong>Buyer:</strong> {{ $requisition->buyer?->name ?? '—' }}
                                @if($requisition->style) &nbsp;|&nbsp; <strong>Style:</strong> {{ $requisition->style }} @endif
                                @if($requisition->order_ref) &nbsp;|&nbsp; <strong>Order Ref:</strong> {{ $requisition->order_ref }} @endif
                                <span class="text-muted">(inherited from the requisition)</span>
                            </div>
                        </div>
                    @else
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Buyer</label>
                            <select name="buyer_id" class="form-control inv-select2">
                                <option value="">— None —</option>
                                @foreach($buyers as $buyer)
                                    <option value="{{ $buyer->id }}" @selected(old('buyer_id') == $buyer->id)>{{ $buyer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Style</label>
                            <input type="text" name="style" class="form-control" value="{{ old('style') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Order Ref</label>
                            <input type="text" name="order_ref" class="form-control" value="{{ old('order_ref') }}">
                        </div>
                    @endif
                    <div class="col-12 mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Items</h6>
                    @unless($requisition)
                        <button type="button" class="btn btn-sm btn-outline-primary" data-line-items-add="iss"><i class="fa-solid fa-plus"></i> Add Row</button>
                    @endunless
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead><tr><th style="min-width:220px">Item</th><th style="width:140px">Remaining Approved</th><th style="width:160px">Issue Qty</th><th style="width:40px"></th></tr></thead>
                        <tbody id="issRowsBody">
                            @php
                                $lines = old('items', $requisition
                                    ? $requisition->items->map(fn ($i) => ['requisition_item_id' => $i->id, 'item_id' => $i->item_id, 'remaining' => $i->approved_qty - $i->issued_qty])->all()
                                    : [[]]);
                            @endphp
                            @foreach($lines as $index => $line)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][item_id]" class="form-control inv-select2" required @if(isset($line['requisition_item_id'])) disabled @endif>
                                            <option value="">— Select —</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" @selected(($line['item_id'] ?? null) == $item->id)>{{ $item->item_code }} — {{ $item->item_name }}</option>
                                            @endforeach
                                        </select>
                                        @if(isset($line['requisition_item_id']))
                                            <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $line['item_id'] }}">
                                            <input type="hidden" name="items[{{ $index }}][requisition_item_id]" value="{{ $line['requisition_item_id'] }}">
                                        @endif
                                    </td>
                                    <td>{{ $line['remaining'] ?? '—' }}</td>
                                    <td><input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][issued_qty]" class="form-control" value="{{ $line['remaining'] ?? '' }}" required></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <template id="issRowTemplate">
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
                        <td><input type="number" step="0.0001" min="0.0001" name="items[__INDEX__][issued_qty]" class="form-control" required></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" data-line-items-remove><i class="fa-solid fa-xmark"></i></button></td>
                    </tr>
                </template>

                <button type="submit" class="btn btn-primary mt-3">Issue &amp; Update Stock</button>
                <a href="{{ route('inventory.issues.index') }}" class="btn btn-light mt-3">Cancel</a>
            </form>
        </div>
    </div>
</div>

@include('sfl-inventory::admin.partials.select2-init')
@include('sfl-inventory::admin.partials.line-items-script')
@push('js')
<script>
    document.querySelector('select[name="department_id"]')?.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const defaultStore = opt.getAttribute('data-default-store');
        const toStoreSelect = document.getElementById('toStoreSelect');
        if (defaultStore && toStoreSelect) {
            $(toStoreSelect).val(defaultStore).trigger('change');
        }
    });
</script>
@endpush
@endsection
