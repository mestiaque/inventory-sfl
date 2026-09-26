{{-- props: unit (optional, for edit) --}}
<div class="row">
<div class="col-md-3 mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $unit?->name ?? '') }}" required>
</div>
<div class="col-md-3 mb-3">
    <label class="form-label">Short Name <span class="text-danger">*</span></label>
    <input type="text" name="short_name" class="form-control form-control-sm" value="{{ old('short_name', $unit?->short_name ?? '') }}" required>
</div>
<div class="col-md-3 mb-3 d-flex align-items-center"><div class="custom-control custom-switch mb-4">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="unitActive{{ $unit?->id ?? 'new' }}"
        @checked(old('is_active', $unit?->is_active ?? true))>
    <label class="custom-control-label" for="unitActive{{ $unit?->id ?? 'new' }}">Active</label>
</div>
</div></div>
