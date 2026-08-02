<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvItemCategoryRequest;
use ME\SflInventory\Models\InvItemCategory;

class InvItemCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_item_category.list');

        $categories = InvItemCategory::query()
            ->with('parent')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('parent_id'), fn ($q) => $q->where('parent_id', $request->parent_id))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $parents = InvItemCategory::active()->parents()->orderBy('name')->get();

        return view('sfl-inventory::admin.item-categories.index', compact('categories', 'parents'));
    }

    public function store(InvItemCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        InvItemCategory::create($data);

        return back()->with('success', 'Item category created successfully.');
    }

    public function update(InvItemCategoryRequest $request, InvItemCategory $item_category): RedirectResponse
    {
        $item_category->update($request->validated());

        return back()->with('success', 'Item category updated successfully.');
    }

    public function destroy(InvItemCategory $item_category): RedirectResponse
    {
        $this->authorize('inv_item_category.delete');

        if ($item_category->isReferenced()) {
            return back()->with('error', 'This category is in use and cannot be deleted.');
        }

        $item_category->delete();

        return back()->with('success', 'Item category deleted successfully.');
    }
}
