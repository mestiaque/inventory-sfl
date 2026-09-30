<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvRequisitionPurposeRequest;
use ME\SflInventory\Models\InvRequisitionPurpose;

/** "Requisition For" master — the options on the Store Requisition form and its printed form. */
class InvRequisitionPurposeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_requisition_purpose.list');

        $purposes = InvRequisitionPurpose::query()
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', '%' . $request->search . '%')->orWhere('code', 'like', '%' . $request->search . '%')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        $trashedPurposes = InvRequisitionPurpose::onlyTrashed()->latest('deleted_at')->get()
            ->map(fn ($purpose) => ['id' => $purpose->id, 'title' => $purpose->name, 'subtitle' => $purpose->code, 'deleted_at' => $purpose->deleted_at]);

        return view('sfl-inventory::admin.requisition-purposes.index', compact('purposes', 'trashedPurposes'));
    }

    public function store(InvRequisitionPurposeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        $data['sort_order'] ??= (int) InvRequisitionPurpose::max('sort_order') + 1;
        InvRequisitionPurpose::create($data);

        return back()->with('success', 'Requisition For option created successfully.');
    }

    public function update(InvRequisitionPurposeRequest $request, InvRequisitionPurpose $requisition_purpose): RedirectResponse
    {
        $requisition_purpose->update($request->validated());

        return back()->with('success', 'Requisition For option updated successfully.');
    }

    public function destroy(InvRequisitionPurpose $requisition_purpose): RedirectResponse
    {
        $this->authorize('inv_requisition_purpose.delete');

        if ($requisition_purpose->isReferenced()) {
            return back()->with('error', 'This option is used by requisitions and cannot be deleted — make it Inactive instead.');
        }

        $requisition_purpose->delete();

        return back()->with('success', 'Requisition For option deleted successfully.');
    }

    public function restore(InvRequisitionPurpose $requisition_purpose): RedirectResponse
    {
        $this->authorize('inv_requisition_purpose.delete');

        $requisition_purpose->restore();

        return back()->with('success', 'Requisition For option restored successfully.');
    }

    public function forceDestroy(InvRequisitionPurpose $requisition_purpose): RedirectResponse
    {
        $this->authorize('inv_requisition_purpose.force_delete');

        if ($requisition_purpose->isReferenced()) {
            return back()->with('error', 'This option is used by requisitions and cannot be deleted.');
        }

        $requisition_purpose->forceDelete();

        return back()->with('success', 'Requisition For option permanently deleted.');
    }
}
