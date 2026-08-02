{{-- props: operator (optional, for edit), stores, users --}}
<div class="mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $operator->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Code <span class="text-danger">*</span></label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $operator->code ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">System Login <span class="text-danger">*</span></label>
    <select name="user_id" class="form-control inv-select2" required>
        <option value="">— Select —</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" @selected(old('user_id', $operator->user_id ?? '') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
        @endforeach
    </select>
    <div class="form-text">The person logs in with this account; their issues/requisitions/etc. get tracked back to this profile.</div>
</div>
<div class="mb-3">
    <label class="form-label">Designation <span class="text-danger">*</span></label>
    <select name="designation" class="form-control operator-designation" required>
        <option value="operator" @selected(old('designation', $operator->designation ?? 'operator') === 'operator')>Operator — sees only their own entries</option>
        <option value="store_incharge" @selected(old('designation', $operator->designation ?? '') === 'store_incharge')>Store Incharge — sees all entries for their store</option>
        <option value="store_manager" @selected(old('designation', $operator->designation ?? '') === 'store_manager')>Store Manager — sees all entries for their store</option>
    </select>
</div>
<div class="mb-3 operator-store-field">
    <label class="form-label">Assigned Store</label>
    <select name="store_id" class="form-control inv-select2">
        <option value="">— None —</option>
        @foreach($stores as $store)
            <option value="{{ $store->id }}" @selected(old('store_id', $operator->store_id ?? '') == $store->id)>{{ $store->name }}</option>
        @endforeach
    </select>
    <div class="form-text">Required for Store Incharge / Store Manager.</div>
</div>
<div class="form-check form-switch">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="operatorActive{{ $operator->id ?? 'new' }}"
        @checked(old('is_active', $operator->is_active ?? true))>
    <label class="form-check-label" for="operatorActive{{ $operator->id ?? 'new' }}">Active</label>
</div>
