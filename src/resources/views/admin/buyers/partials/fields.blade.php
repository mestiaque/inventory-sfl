{{-- props: buyer (optional, for edit) --}}
<div class="mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $buyer->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Code <span class="text-danger">*</span></label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $buyer->code ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Contact</label>
    <input type="text" name="contact" class="form-control" value="{{ old('contact', $buyer->contact ?? '') }}">
</div>
<div class="mb-3">
    <label class="form-label">Address</label>
    <input type="text" name="address" class="form-control" value="{{ old('address', $buyer->address ?? '') }}">
</div>
<div class="form-check form-switch">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="buyerActive{{ $buyer->id ?? 'new' }}"
        @checked(old('is_active', $buyer->is_active ?? true))>
    <label class="form-check-label" for="buyerActive{{ $buyer->id ?? 'new' }}">Active</label>
</div>
