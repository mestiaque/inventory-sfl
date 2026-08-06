{{-- props: unit (optional, for edit) --}}
<div class="mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $unit?->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Short Name <span class="text-danger">*</span></label>
    <input type="text" name="short_name" class="form-control" value="{{ old('short_name', $unit?->short_name ?? '') }}" required>
</div>
<div class="form-check form-switch mb-4">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="unitActive{{ $unit?->id ?? 'new' }}"
        @checked(old('is_active', $unit?->is_active ?? true))>
    <label class="form-check-label" for="unitActive{{ $unit?->id ?? 'new' }}">Active</label>
</div>
