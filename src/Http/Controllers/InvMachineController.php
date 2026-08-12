<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvMachineRequest;
use ME\SflInventory\Models\InvDepartment;
use ME\SflInventory\Models\InvMachine;

class InvMachineController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_machine.list');

        $machines = InvMachine::query()
            ->with('department')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('code', 'like', '%' . $request->search . '%');
            }))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $departments = InvDepartment::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.machines.index', compact('machines', 'departments'));
    }

    public function store(InvMachineRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        InvMachine::create($data);

        return back()->with('success', 'Machine created successfully.');
    }

    public function update(InvMachineRequest $request, InvMachine $machine): RedirectResponse
    {
        $machine->update($request->validated());

        return back()->with('success', 'Machine updated successfully.');
    }

    public function destroy(InvMachine $machine): RedirectResponse
    {
        $this->authorize('inv_machine.delete');

        if ($machine->isReferenced()) {
            return back()->with('error', 'This machine has related documents and cannot be deleted.');
        }

        $machine->delete();

        return back()->with('success', 'Machine deleted successfully.');
    }
}
