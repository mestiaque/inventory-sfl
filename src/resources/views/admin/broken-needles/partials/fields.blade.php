{{-- props: entry (optional, for edit), employees, departments, machines, buyers --}}
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Employee (Operator) <span class="text-danger">*</span></label>
        <select name="employee_id" class="form-control inv-select2" required>
            <option value="">— Select —</option>
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}" @selected(old('employee_id', $entry?->employee_id ?? '') == $employee->id)>{{ $employee->name }} ({{ $employee->employee_id }})</option>
            @endforeach
        </select>
        <div class="form-text">Operator Card No shown on the printed report is this employee's ID.</div>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-control inv-select2">
            <option value="">— None —</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $entry?->department_id ?? '') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Line No <span class="text-danger">*</span></label>
        <input type="text" name="line_no" class="form-control" value="{{ old('line_no', $entry?->line_no ?? '') }}" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Machine</label>
        <select name="machine_id" class="form-control inv-select2">
            <option value="">— None —</option>
            @foreach($machines as $machine)
                <option value="{{ $machine->id }}" @selected(old('machine_id', $entry?->machine_id ?? '') == $machine->id)>{{ $machine->name }} ({{ $machine->code }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Date <span class="text-danger">*</span></label>
        <input type="date" name="broken_date" class="form-control" value="{{ old('broken_date', optional($entry?->broken_date)->format('Y-m-d') ?? now()->toDateString()) }}" required>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Type of Needle Name <span class="text-danger">*</span></label>
        <input type="text" name="needle_type" class="form-control" placeholder="e.g. DPX17-14" value="{{ old('needle_type', $entry?->needle_type ?? '') }}" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Needle Size <span class="text-danger">*</span></label>
        <input type="text" name="needle_size" class="form-control" placeholder="e.g. 14" value="{{ old('needle_size', $entry?->needle_size ?? '') }}" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Quantity (pcs) <span class="text-danger">*</span></label>
        <input type="number" min="1" step="1" name="quantity" class="form-control" value="{{ old('quantity', $entry?->quantity ?? 1) }}" required>
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Remarks</label>
    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $entry?->remarks ?? '') }}</textarea>
</div>
