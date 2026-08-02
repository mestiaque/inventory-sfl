<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvStoreRequest;
use ME\SflInventory\Models\InvStore;

class InvStoreController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_store.list');

        $stores = InvStore::query()
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('code', 'like', '%' . $request->search . '%')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sfl-inventory::admin.stores.index', compact('stores'));
    }

    public function store(InvStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        InvStore::create($data);

        return back()->with('success', 'Store created successfully.');
    }

    public function update(InvStoreRequest $request, InvStore $store): RedirectResponse
    {
        $store->update($request->validated());

        return back()->with('success', 'Store updated successfully.');
    }

    public function destroy(InvStore $store): RedirectResponse
    {
        $this->authorize('inv_store.delete');

        if ($store->isReferenced()) {
            return back()->with('error', 'This store has stock movements and cannot be deleted.');
        }

        $store->delete();

        return back()->with('success', 'Store deleted successfully.');
    }
}
