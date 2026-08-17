<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Blocks on the same real documents isReferenced() checks (requisition,
     * issue, production consumption — restrictOnDelete FKs), then, if clear,
     * clears the softer/nullable references (stock ledger, broken needles,
     * machines, items' own department field) before permanently removing
     * the department — matching the item force-delete pattern.
     */
    public function forceDestroy(InvDepartment $department): RedirectResponse
    {
        $this->authorize('inv_department.force_delete');

        if ($department->isReferenced()) {
            return back()->with('error', 'Cannot force delete: this department is used on real Requisition, Issue, or Production Consumption documents. Remove those first.');
        }

        DB::transaction(function () use ($department) {
            DB::table('inv_stock_transactions')->where('department_id', $department->id)->update(['department_id' => null]);
            DB::table('inv_broken_needles')->where('department_id', $department->id)->update(['department_id' => null]);
            DB::table('inv_machines')->where('department_id', $department->id)->update(['department_id' => null]);
            DB::table('inv_items')->where('department_id', $department->id)->update(['department_id' => null]);

            $department->forceDelete();
        });

        return back()->with('success', 'Department permanently deleted.');
    }
}
