{{-- props: supplier (optional, for edit) --}}
<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $supplier?->name ?? '') }}" required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Code <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control form-control-sm" value="{{ old('code', $supplier?->code ?? '') }}" required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Contact Person</label>
        <input type="text" name="contact_person" class="form-control form-control-sm" value="{{ old('contact_person', $supplier?->contact_person ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control form-control-sm" value="{{ old('phone', $supplier?->phone ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Products / Items Supplied</label>
        <input type="text" name="products_supplied" class="form-control form-control-sm" placeholder="e.g. Trims &amp; Accessories" value="{{ old('products_supplied', $supplier?->products_supplied ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Business Relation Since</label>
        <input type="number" name="relation_since_year" class="form-control form-control-sm" placeholder="e.g. 2024" min="1980" max="{{ date('Y') + 1 }}" value="{{ old('relation_since_year', $supplier?->relation_since_year ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control form-control-sm" value="{{ old('email', $supplier?->email ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">TIN/VAT</label>
        <input type="text" name="tin_vat" class="form-control form-control-sm" value="{{ old('tin_vat', $supplier?->tin_vat ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Price Rating (1-5)</label>
        <select name="price_rating" class="form-control form-control-sm">
            <option value="">— Select —</option>
            @for($i = 1; $i <= 5; $i++)
                <option value="{{ $i }}" @selected((int) old('price_rating', $supplier?->price_rating) === $i)>{{ $i }}</option>
            @endfor
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Quality Rating (1-5)</label>
        <select name="quality_rating" class="form-control form-control-sm">
            <option value="">— Select —</option>
            @for($i = 1; $i <= 5; $i++)
                <option value="{{ $i }}" @selected((int) old('quality_rating', $supplier?->quality_rating) === $i)>{{ $i }}</option>
            @endfor
        </select>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control form-control-sm" value="{{ old('address', $supplier?->address ?? '') }}">
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control form-control-sm" rows="2">{{ old('remarks', $supplier?->remarks ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <div class="custom-control custom-switch mb-4">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="supplierActive{{ $supplier?->id ?? 'new' }}"
                @checked(old('is_active', $supplier?->is_active ?? true))>
            <label class="custom-control-label" for="supplierActive{{ $supplier?->id ?? 'new' }}">Active</label>
        </div>
    </div>
</div>
