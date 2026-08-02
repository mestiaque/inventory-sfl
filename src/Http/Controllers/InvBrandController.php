<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvBrandRequest;
use ME\SflInventory\Models\InvBrand;

class InvBrandController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_brand.list');

        $brands = InvBrand::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sfl-inventory::admin.brands.index', compact('brands'));
    }

    public function store(InvBrandRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        InvBrand::create($data);

        return back()->with('success', 'Brand created successfully.');
    }

    public function update(InvBrandRequest $request, InvBrand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        return back()->with('success', 'Brand updated successfully.');
    }

    public function destroy(InvBrand $brand): RedirectResponse
    {
        $this->authorize('inv_brand.delete');

        if ($brand->isReferenced()) {
            return back()->with('error', 'This brand is in use and cannot be deleted.');
        }

        $brand->delete();

        return back()->with('success', 'Brand deleted successfully.');
    }
}
