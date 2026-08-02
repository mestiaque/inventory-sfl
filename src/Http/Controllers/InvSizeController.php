<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvSizeRequest;
use ME\SflInventory\Models\InvSize;

class InvSizeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_size.list');

        $sizes = InvSize::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('sfl-inventory::admin.sizes.index', compact('sizes'));
    }

    public function store(InvSizeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        InvSize::create($data);

        return back()->with('success', 'Size created successfully.');
    }

    public function update(InvSizeRequest $request, InvSize $size): RedirectResponse
    {
        $size->update($request->validated());

        return back()->with('success', 'Size updated successfully.');
    }

    public function destroy(InvSize $size): RedirectResponse
    {
        $this->authorize('inv_size.delete');

        if ($size->isReferenced()) {
            return back()->with('error', 'This size is in use and cannot be deleted.');
        }

        $size->delete();

        return back()->with('success', 'Size deleted successfully.');
    }
}
