<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvUnitRequest;
use ME\SflInventory\Models\InvUnit;

class InvUnitController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_unit.list');

        $units = InvUnit::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sfl-inventory::admin.units.index', compact('units'));
    }

    public function store(InvUnitRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        InvUnit::create($data);

        return back()->with('success', 'Unit created successfully.');
    }

    public function update(InvUnitRequest $request, InvUnit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return back()->with('success', 'Unit updated successfully.');
    }

    public function destroy(InvUnit $unit): RedirectResponse
    {
        $this->authorize('inv_unit.delete');

        if ($unit->isReferenced()) {
            return back()->with('error', 'This unit is in use and cannot be deleted.');
        }

        $unit->delete();

        return back()->with('success', 'Unit deleted successfully.');
    }
}
