{{-- props: department (optional, for edit), stores --}}
<div class="mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $department->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Code <span class="text-danger">*</span></label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $department->code ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Default Floor Store</label>
    <select name="default_store_id" class="form-control inv-select2">
        <option value="">— None —</option>
        @foreach($stores as $store)
            <option value="{{ $store->id }}" @selected(old('default_store_id', $department->default_store_id ?? '') == $store->id)>{{ $store->name }}</option>
        @endforeach
    </select>
    <div class="form-text">Auto-fills the "to store" on Store Issue forms for this department (e.g. Cutting → Cutting Store).</div>
</div>
<div class="form-check form-switch">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="departmentActive{{ $department->id ?? 'new' }}"
        @checked(old('is_active', $department->is_active ?? true))>
    <label class="form-check-label" for="departmentActive{{ $department->id ?? 'new' }}">Active</label>
</div>
