{{-- props: buyer (optional, for edit) --}}
<div class="row">
<div class="col-md-3 mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $buyer?->name ?? '') }}" required>
</div>
<div class="col-md-3 mb-3">
    <label class="form-label">Code <span class="text-danger">*</span></label>
    <input type="text" name="code" class="form-control form-control-sm" value="{{ old('code', $buyer?->code ?? '') }}" required>
</div>
<div class="col-md-3 mb-3">
    <label class="form-label">Contact</label>
    <input type="text" name="contact" class="form-control form-control-sm" value="{{ old('contact', $buyer?->contact ?? '') }}">
</div>
<div class="col-md-3 mb-3">
    <label class="form-label">Address</label>
    <input type="text" name="address" class="form-control form-control-sm" value="{{ old('address', $buyer?->address ?? '') }}">
</div>
<div class="col-md-3 mb-3 d-flex align-items-center"><div class="custom-control custom-switch mb-4">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="buyerActive{{ $buyer?->id ?? 'new' }}"
        @checked(old('is_active', $buyer?->is_active ?? true))>
    <label class="custom-control-label" for="buyerActive{{ $buyer?->id ?? 'new' }}">Active</label>
</div>
</div></div>
