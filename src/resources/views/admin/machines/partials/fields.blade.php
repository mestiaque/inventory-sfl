{{-- props: machine (optional, for edit), departments --}}
<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Machine Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $machine?->name ?? '') }}" required>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Machine Code <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control form-control-sm" value="{{ old('code', $machine?->code ?? '') }}" required>
    </div>
</div>
<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Machine No</label>
        <input type="text" name="machine_no" class="form-control form-control-sm" placeholder="e.g. B.A, B.St, SN2" value="{{ old('machine_no', $machine?->machine_no ?? '') }}">
        <div class="form-text">The floor's own machine tag — this is what prints in the "Machine No" column on reports.</div>
    </div>
    <div class="col-md-6"></div>
</div>
<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Model</label>
        <input type="text" name="model" class="form-control form-control-sm" value="{{ old('model', $machine?->model ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Origin</label>
        <input type="text" name="origin" class="form-control form-control-sm" value="{{ old('origin', $machine?->origin ?? '') }}">
    </div>
</div>
<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Type</label>
        <input type="text" name="type" class="form-control form-control-sm" value="{{ old('type', $machine?->type ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Color</label>
        <input type="text" name="color" class="form-control form-control-sm" value="{{ old('color', $machine?->color ?? '') }}">
    </div>
</div>
<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-control form-control-sm inv-select2">
            <option value="">— None —</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $machine?->department_id ?? '') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Section</label>
        <input type="text" name="section" class="form-control form-control-sm" value="{{ old('section', $machine?->section ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Line</label>
        <input type="text" name="line" class="form-control form-control-sm" value="{{ old('line', $machine?->line ?? '') }}">
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control form-control-sm" rows="2">{{ old('description', $machine?->description ?? '') }}</textarea>
</div>
<div class="custom-control custom-switch mb-4">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="machineActive{{ $machine?->id ?? 'new' }}"
        @checked(old('is_active', $machine?->is_active ?? true))>
    <label class="custom-control-label" for="machineActive{{ $machine?->id ?? 'new' }}">Active</label>
</div>
