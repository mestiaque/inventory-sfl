{{-- props: category (optional, for edit), parents --}}
<div class="row">
<div class="col-md-3 mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $category?->name ?? '') }}" required>
</div>
<div class="col-md-3 mb-3">
    <label class="form-label">Code</label>
    <input type="text" name="code" class="form-control form-control-sm" value="{{ old('code', $category?->code ?? '') }}">
</div>
<div class="col-md-3 mb-3">
    <label class="form-label">Parent Category</label>
    <select name="parent_id" class="form-control form-control-sm inv-select2">
        <option value="">— None (top level) —</option>
        @foreach($parents as $parent)
            @continue(isset($category) && $parent->id === $category?->id)
            <option value="{{ $parent->id }}" @selected(old('parent_id', $category?->parent_id ?? '') == $parent->id)>{{ $parent->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-3 mb-3 d-flex align-items-center"><div class="custom-control custom-switch mb-4">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="categoryActive{{ $category?->id ?? 'new' }}"
        @checked(old('is_active', $category?->is_active ?? true))>
    <label class="custom-control-label" for="categoryActive{{ $category?->id ?? 'new' }}">Active</label>
</div>
</div></div>
