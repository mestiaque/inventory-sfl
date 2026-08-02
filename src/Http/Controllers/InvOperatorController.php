<?php

namespace ME\SflInventory\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvOperatorRequest;
use ME\SflInventory\Models\InvOperator;
use ME\SflInventory\Models\InvStore;

class InvOperatorController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_operator.list');

        $operators = InvOperator::query()
            ->with(['user', 'store'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('designation'), fn ($q) => $q->where('designation', $request->designation))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sfl-inventory::admin.operators.index', compact('operators') + $this->formOptions());
    }

    public function store(InvOperatorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        InvOperator::create($data);

        return back()->with('success', 'Operator created successfully.');
    }

    public function update(InvOperatorRequest $request, InvOperator $operator): RedirectResponse
    {
        $operator->update($request->validated());

        return back()->with('success', 'Operator updated successfully.');
    }

    public function destroy(InvOperator $operator): RedirectResponse
    {
        $this->authorize('inv_operator.delete');

        $operator->delete();

        return back()->with('success', 'Operator deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'stores' => InvStore::active()->orderBy('name')->get(),
            'users'  => User::orderBy('name')->get(),
        ];
    }
}
