<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvSupplierRequest;
use ME\SflInventory\Models\InvSupplier;

class InvSupplierController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_supplier.list');

        $suppliers = InvSupplier::query()
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('code', 'like', '%' . $request->search . '%')
                ->orWhere('phone', 'like', '%' . $request->search . '%')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sfl-inventory::admin.suppliers.index', compact('suppliers'));
    }

    public function store(InvSupplierRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        InvSupplier::create($data);

        return back()->with('success', 'Supplier created successfully.');
    }

    public function update(InvSupplierRequest $request, InvSupplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return back()->with('success', 'Supplier updated successfully.');
    }

    public function destroy(InvSupplier $supplier): RedirectResponse
    {
        $this->authorize('inv_supplier.delete');

        if ($supplier->isReferenced()) {
            return back()->with('error', 'This supplier has purchase orders and cannot be deleted.');
        }

        $supplier->delete();

        return back()->with('success', 'Supplier deleted successfully.');
    }
}
