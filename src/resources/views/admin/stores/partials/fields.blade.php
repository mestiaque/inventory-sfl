{{-- props: store (optional, for edit) --}}
<div class="mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $store->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Code <span class="text-danger">*</span></label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $store->code ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Type <span class="text-danger">*</span></label>
    <select name="type" class="form-control inv-select2" required>
        @foreach(['raw_material' => 'Raw Material', 'accessories' => 'Accessories', 'cutting' => 'Cutting', 'sewing' => 'Sewing', 'finishing' => 'Finishing', 'finished_goods' => 'Finished Goods', 'general' => 'General'] as $value => $label)
            <option value="{{ $value }}" @selected(old('type', $store->type ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Address</label>
    <input type="text" name="address" class="form-control" value="{{ old('address', $store->address ?? '') }}">
</div>
<div class="form-check form-switch">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="storeActive{{ $store->id ?? 'new' }}"
        @checked(old('is_active', $store->is_active ?? true))>
    <label class="form-check-label" for="storeActive{{ $store->id ?? 'new' }}">Active</label>
</div>
