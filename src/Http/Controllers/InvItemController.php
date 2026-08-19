<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvItemRequest;
use ME\SflInventory\Models\InvBrand;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Models\InvColor;
use ME\SflInventory\Models\InvDepartment;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvItemCategory;
use ME\SflInventory\Models\InvSize;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Models\InvSupplier;
use ME\SflInventory\Models\InvUnit;

class InvItemController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_item.list');

        $items = InvItem::query()
            ->with(['category', 'unit', 'brand', 'color', 'size', 'department', 'supplier', 'buyer', 'openingStore'])
            ->when($request->filled('item_code'), fn ($q) => $q->where('item_code', 'like', '%' . $request->item_code . '%'))
            ->when($request->filled('item_name'), fn ($q) => $q->where('item_name', 'like', '%' . $request->item_name . '%'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('unit_id'), fn ($q) => $q->where('unit_id', $request->unit_id))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('store_id'), fn ($q) => $q->where('opening_store_id', $request->store_id))
            ->when($request->filled('item_type'), fn ($q) => $q->where('item_type', $request->item_type))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $categories = InvItemCategory::active()->orderBy('name')->get();
        $departments = InvDepartment::active()->orderBy('name')->get();
        $suppliers = InvSupplier::active()->orderBy('name')->get();
        $units = InvUnit::active()->orderBy('name')->get();
        $stores = InvStore::active()->orderBy('name')->get();
        $buyers = InvBuyer::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.items.index', compact('items', 'categories', 'departments', 'suppliers', 'units', 'stores', 'buyers'));
    }

    public function create(): View
    {
        $this->authorize('inv_item.add');

        return view('sfl-inventory::admin.items.create', $this->formOptions());
    }

    public function store(InvItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        $item = InvItem::create($data);

        return redirect()->route('inventory.items.index')->with('success', "Item {$item->item_code} created successfully.");
    }

    public function edit(InvItem $item): View
    {
        $this->authorize('inv_item.edit');

        return view('sfl-inventory::admin.items.edit', ['item' => $item] + $this->formOptions());
    }

    public function update(InvItemRequest $request, InvItem $item): RedirectResponse
    {
        $item->update($request->validated());

        return redirect()->route('inventory.items.index')->with('success', 'Item updated successfully.');
    }

    public function destroy(InvItem $item): RedirectResponse
    {
        $this->authorize('inv_item.delete');

        if ($item->isReferenced()) {
            return back()->with('error', 'This item has stock movements and cannot be deleted.');
        }

        $item->delete();

        return back()->with('success', 'Item deleted successfully.');
    }

    /**
     * Wipes the item's stock ledger (inv_stock_transactions) and permanently
     * removes the item itself — for cleaning up test/junk items that were
     * never part of a real GRN/requisition/issue/etc. Checked explicitly
     * against every document line-item table rather than relying on their
     * `restrictOnDelete()` foreign keys to block it: this database has rows
     * whose FK constraints don't actually exist (verified via
     * information_schema — a pre-existing gap, not something this method
     * should ever depend on for safety).
     */
    public function forceDestroy(InvItem $item): RedirectResponse
    {
        $this->authorize('inv_item.force_delete');

        $referencingTables = [
            'GRN'                      => 'inv_grn_items',
            'Purchase Order'           => 'inv_purchase_order_items',
            'Requisition'              => 'inv_requisition_items',
            'Issue'                    => 'inv_issue_items',
            'Stock Transfer'           => 'inv_stock_transfer_items',
            'Production Consumption'   => 'inv_production_consumption_items',
            'Finished Goods Receive'   => 'inv_finished_goods_receive_items',
            'Gate Pass'                => 'inv_gate_pass_items',
            'Shipment'                 => 'inv_shipment_items',
            'Stock Adjustment'         => 'inv_stock_adjustment_items',
        ];

        foreach ($referencingTables as $label => $table) {
            if (DB::table($table)->where('item_id', $item->id)->exists()) {
                return back()->with('error', "Cannot force delete: this item is used on a real {$label} document. Remove that document first.");
            }
        }

        DB::transaction(function () use ($item) {
            $item->stockTransactions()->delete();
            $item->forceDelete();
        });

        return back()->with('success', 'Item and its stock history permanently deleted.');
    }

    /**
     * Gated behind the standalone "Barcode System" permission (not
     * inv_item.*) since the same permission also unlocks scan-to-receive on
     * the GRN screens — one flag for the whole feature, per the spec's
     * "permission thakle use hobe na hole hobe na" requirement. Only items
     * explicitly opted in (barcode_enabled) get one generated; this is the
     * "user tar posondomoto product e barcode generate korte parbe" part —
     * small/loose items can be left off.
     */
    public function generateBarcode(InvItem $item): RedirectResponse
    {
        $this->authorize('inv_barcode.use');

        if (! $item->barcode_enabled) {
            return back()->with('error', 'Enable barcode for this item first (Edit → Enable Barcode), then generate it.');
        }

        $item->update(['barcode' => config('sfl-inventory.document_prefixes.company', 'SFL') . str_pad((string) $item->id, 9, '0', STR_PAD_LEFT)]);

        return back()->with('success', "Barcode generated: {$item->barcode}");
    }

    private function formOptions(): array
    {
        return [
            'categories'  => InvItemCategory::active()->orderBy('name')->get(),
            'units'       => InvUnit::active()->orderBy('name')->get(),
            'brands'      => InvBrand::active()->orderBy('name')->get(),
            'colors'      => InvColor::active()->orderBy('name')->get(),
            'sizes'       => InvSize::active()->ordered()->get(),
            'stores'      => InvStore::active()->orderBy('name')->get(),
            'departments' => InvDepartment::active()->orderBy('name')->get(),
            'suppliers'   => InvSupplier::active()->orderBy('name')->get(),
            'buyers'      => InvBuyer::active()->orderBy('name')->get(),
        ];
    }
}
