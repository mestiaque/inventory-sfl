<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvBuyerRequest;
use ME\SflInventory\Models\InvBuyer;

class InvBuyerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_buyer.list');

        $buyers = InvBuyer::query()
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('code', 'like', '%' . $request->search . '%')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sfl-inventory::admin.buyers.index', compact('buyers'));
    }

    public function store(InvBuyerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        InvBuyer::create($data);

        return back()->with('success', 'Buyer created successfully.');
    }

    public function update(InvBuyerRequest $request, InvBuyer $buyer): RedirectResponse
    {
        $buyer->update($request->validated());

        return back()->with('success', 'Buyer updated successfully.');
    }

    public function destroy(InvBuyer $buyer): RedirectResponse
    {
        $this->authorize('inv_buyer.delete');

        if ($buyer->isReferenced()) {
            return back()->with('error', 'This buyer has related documents and cannot be deleted.');
        }

        $buyer->delete();

        return back()->with('success', 'Buyer deleted successfully.');
    }
}
