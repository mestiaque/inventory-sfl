<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvColorRequest;
use ME\SflInventory\Models\InvColor;

class InvColorController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_color.list');

        $colors = InvColor::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sfl-inventory::admin.colors.index', compact('colors'));
    }

    public function store(InvColorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        InvColor::create($data);

        return back()->with('success', 'Color created successfully.');
    }

    public function update(InvColorRequest $request, InvColor $color): RedirectResponse
    {
        $color->update($request->validated());

        return back()->with('success', 'Color updated successfully.');
    }

    public function destroy(InvColor $color): RedirectResponse
    {
        $this->authorize('inv_color.delete');

        if ($color->isReferenced()) {
            return back()->with('error', 'This color is in use and cannot be deleted.');
        }

        $color->delete();

        return back()->with('success', 'Color deleted successfully.');
    }
}
