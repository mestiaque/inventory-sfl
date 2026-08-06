{{-- props: store (optional, for edit) --}}
<div class="mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $store?->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Code <span class="text-danger">*</span></label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $store?->code ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Store For <span class="text-danger">*</span></label>
    <select name="type" class="form-control inv-select2" required>
        <option value="">— Select —</option>
        <option value="raw_material" @selected(old('type', $store?->type ?? '') === 'raw_material')>For Buyer (Warehouse)</option>
        <option value="accessories" @selected(old('type', $store?->type ?? '') === 'accessories')>For Accessories</option>
        <option value="finished_goods" @selected(old('type', $store?->type ?? '') === 'finished_goods')>For Finished Goods</option>
    </select>
    <div class="form-text">Determines where this store is auto-selected/locked across Purchase, Requisition and Finished Goods screens.</div>
</div>
<div class="mb-3">
    <label class="form-label">Address</label>
    <input type="text" name="address" class="form-control" value="{{ old('address', $store?->address ?? '') }}">
</div>
<div class="form-check form-switch mb-4">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="storeActive{{ $store?->id ?? 'new' }}"
        @checked(old('is_active', $store?->is_active ?? true))>
    <label class="form-check-label" for="storeActive{{ $store?->id ?? 'new' }}">Active</label>
</div>
