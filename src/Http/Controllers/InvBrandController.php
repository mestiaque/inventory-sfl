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

        $trashedBrands = InvBrand::onlyTrashed()->latest('deleted_at')->get()
            ->map(fn ($brand) => ['id' => $brand->id, 'title' => $brand->name, 'subtitle' => null, 'deleted_at' => $brand->deleted_at]);

        return view('sfl-inventory::admin.brands.index', compact('brands', 'trashedBrands'));
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

    public function restore(InvBrand $brand): RedirectResponse
    {
        $this->authorize('inv_brand.delete');

        $brand->restore();

        return back()->with('success', 'Brand restored successfully.');
    }

    public function forceDestroy(InvBrand $brand): RedirectResponse
    {
        $this->authorize('inv_brand.force_delete');

        $brand->forceDelete();

        return back()->with('success', 'Brand permanently deleted.');
    }
}
