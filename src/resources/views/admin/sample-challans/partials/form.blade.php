{{-- props: challan (model, may be unsaved), stores, merBuyersOptions, orderPoOptions, sampleTypeOptions, sizes --}}
@php
    $lines = old('items', $challan->relationLoaded('items') ? $challan->items->toArray() : []);
@endphp
<div class="form-row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Challan Date <span class="text-danger">*</span></label>
        <input type="date" name="challan_date" class="form-control form-control-sm" value="{{ old('challan_date', optional($challan->challan_date)->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Store</label>
        <select name="store_id" class="form-control form-control-sm inv-select2">
            <option value="">— None —</option>
            @foreach($stores as $store)
                <option value="{{ $store->id }}" @selected((string) old('store_id', $challan->store_id) === (string) $store->id)>{{ $store->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Buyer</label>
        <select name="msfl_buyer_id" class="form-control form-control-sm inv-select2">
            <option value="">— None —</option>
            @foreach($merBuyersOptions as $buyer)
                <option value="{{ $buyer->id }}" @selected((string) old('msfl_buyer_id', $challan->msfl_buyer_id) === (string) $buyer->id)>{{ $buyer->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Sample Type</label>
        <select name="msfl_sample_type_id" class="form-control form-control-sm inv-select2" data-sci-sample-type>
            <option value="" data-code="P.P">— P.P (default) —</option>
            @foreach($sampleTypeOptions as $type)
                <option value="{{ $type->id }}" data-code="{{ $type->code }}" @selected((string) old('msfl_sample_type_id', $challan->msfl_sample_type_id) === (string) $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Contact No</label>
        <input type="text" name="to_contact" class="form-control form-control-sm" value="{{ old('to_contact', $challan->to_contact) }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">To <span class="text-danger">*</span></label>
        <input type="text" name="to_name" class="form-control form-control-sm" value="{{ old('to_name', $challan->to_name) }}" required>
    </div>
    <div class="col-md-8 mb-3">
        <label class="form-label">Address</label>
        <textarea name="to_address" rows="2" class="form-control form-control-sm">{{ old('to_address', $challan->to_address) }}</textarea>
    </div>
    <div class="col-md-12 mb-3">
        <label class="form-label">Remarks</label>
        <textarea name="remarks" rows="2" class="form-control form-control-sm">{{ old('remarks', $challan->remarks) }}</textarea>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Items</h6>
    <button type="button" class="btn btn-sm ui-act ui-act-create" data-line-items-add="sci" title="Add Item"><i class="fa-solid fa-plus"></i> Add Item</button>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
        <thead>
            <tr>
                <th>PO / Style / Colour</th><th>Size</th><th>Sample of Buyer</th><th data-sci-pp-header>{{ strtoupper($challan->sample_type_name ?: 'P.P') }} Sample from SFL</th><th>Unit</th><th>Remarks</th><th></th>
            </tr>
        </thead>
        <tbody id="sciRowsBody">
            @foreach(empty($lines) ? [[]] : $lines as $index => $line)
                @include('sfl-inventory::admin.sample-challans.partials.line-row', ['index' => $index, 'line' => $line])
            @endforeach
        </tbody>
    </table>
</div>
<template id="sciRowTemplate">
    @include('sfl-inventory::admin.sample-challans.partials.line-row', ['index' => '__INDEX__', 'line' => []])
</template>
<script>
    document.querySelector('[data-sci-sample-type]')?.addEventListener('change', function () {
        var code = this.options[this.selectedIndex]?.dataset.code || 'P.P';
        var th = document.querySelector('[data-sci-pp-header]');
        if (th) { th.textContent = code.toUpperCase() + ' Sample from SFL'; }
    });
</script>
