{{-- props: store (optional, for edit) --}}
<div class="row">
<div class="col-md-3 mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $store?->name ?? '') }}" required>
</div>
<div class="col-md-3 mb-3">
    <label class="form-label">Code <span class="text-danger">*</span></label>
    <input type="text" name="code" class="form-control form-control-sm" value="{{ old('code', $store?->code ?? '') }}" required>
</div>
<div class="col-md-3 mb-3">
    <label class="form-label">Store For <span class="text-danger">*</span></label>
    <select name="type" class="form-control form-control-sm inv-select2" required>
        <option value="">— Select —</option>
        @foreach(\ME\SflInventory\Models\InvStore::TYPE_LABELS as $typeKey => $typeLabel)
            <option value="{{ $typeKey }}" @selected(old('type', $store?->type ?? '') === $typeKey)>{{ $typeLabel }}</option>
        @endforeach
    </select>
    <div class="form-text">General = purchased accessories · Buyer = goods the buyer sends (style-wise) · Finish = finished garments. Each receive screen only accepts its own kind.</div>
</div>
<div class="col-md-3 mb-3">
    <label class="form-label">Address</label>
    <input type="text" name="address" class="form-control form-control-sm" value="{{ old('address', $store?->address ?? '') }}">
</div>
<div class="col-md-3 mb-3 d-flex align-items-center"><div class="custom-control custom-switch mb-4">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="storeActive{{ $store?->id ?? 'new' }}"
        @checked(old('is_active', $store?->is_active ?? true))>
    <label class="custom-control-label" for="storeActive{{ $store?->id ?? 'new' }}">Active</label>
</div>
</div></div>
