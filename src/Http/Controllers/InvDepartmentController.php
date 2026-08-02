<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvDepartmentRequest;
use ME\SflInventory\Models\InvDepartment;
use ME\SflInventory\Models\InvStore;

class InvDepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_department.list');

        $departments = InvDepartment::query()
            ->with('defaultStore')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.departments.index', compact('departments', 'stores'));
    }

    public function store(InvDepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        InvDepartment::create($data);

        return back()->with('success', 'Department created successfully.');
    }

    public function update(InvDepartmentRequest $request, InvDepartment $department): RedirectResponse
    {
        $department->update($request->validated());

        return back()->with('success', 'Department updated successfully.');
    }

    public function destroy(InvDepartment $department): RedirectResponse
    {
        $this->authorize('inv_department.delete');

        if ($department->isReferenced()) {
            return back()->with('error', 'This department has related documents and cannot be deleted.');
        }

        $department->delete();

        return back()->with('success', 'Department deleted successfully.');
    }
}
