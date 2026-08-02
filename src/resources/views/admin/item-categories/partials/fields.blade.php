{{-- props: category (optional, for edit), parents --}}
<div class="mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Code</label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $category->code ?? '') }}">
</div>
<div class="mb-3">
    <label class="form-label">Parent Category</label>
    <select name="parent_id" class="form-control inv-select2">
        <option value="">— None (top level) —</option>
        @foreach($parents as $parent)
            @continue(isset($category) && $parent->id === $category->id)
            <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id ?? '') == $parent->id)>{{ $parent->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-check form-switch">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="categoryActive{{ $category->id ?? 'new' }}"
        @checked(old('is_active', $category->is_active ?? true))>
    <label class="form-check-label" for="categoryActive{{ $category->id ?? 'new' }}">Active</label>
</div>
