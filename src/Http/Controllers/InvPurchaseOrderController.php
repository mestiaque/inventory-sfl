<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvPurchaseOrderRequest;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvPurchaseOrder;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Models\InvSupplier;
use ME\SflInventory\Services\InvOperatorScopeService;

class InvPurchaseOrderController extends Controller
{
    public function __construct(private readonly InvOperatorScopeService $operatorScope)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_purchase_order.list');

        $purchaseOrders = InvPurchaseOrder::query()
            ->with(['supplier', 'creator'])
            ->withCount(['grns', 'items'])
            ->when($request->filled('search'), fn ($q) => $q->where('po_number', 'like', '%' . $request->search . '%'))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('item_id'), fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('item_id', $request->item_id)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('order_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('order_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToActor($q, 'created_by'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $suppliers = InvSupplier::active()->orderBy('name')->get();
        $items = InvItem::active()->orderBy('item_name')->get();

        return view('sfl-inventory::admin.purchase-orders.index', compact('purchaseOrders', 'suppliers', 'items'));
    }

    public function create(): View
    {
        $this->authorize('inv_purchase_order.add');

        return view('sfl-inventory::admin.purchase-orders.create', $this->formOptions());
    }

    public function store(InvPurchaseOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $po = DB::transaction(function () use ($data) {
            $total = collect($data['items'])->sum(fn ($line) => $line['quantity'] * $line['rate']);

            $po = InvPurchaseOrder::create([
                'supplier_id'   => $data['supplier_id'],
                'order_date'    => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'status'        => 'draft',
                'total_amount'  => $total,
                'remarks'       => $data['remarks'] ?? null,
                'created_by'    => auth()->id(),
            ]);

            foreach ($data['items'] as $line) {
                $po->items()->create([
                    'item_id'  => $line['item_id'],
                    'quantity' => $line['quantity'],
                    'rate'     => $line['rate'],
                    'amount'   => $line['quantity'] * $line['rate'],
                ]);
            }

            return $po;
        });

        return redirect()->route('inventory.purchase-orders.index')->with('success', "Purchase order {$po->po_number} created successfully.");
    }

    public function show(InvPurchaseOrder $purchase_order): View
    {
        $this->authorize('inv_purchase_order.view');

        $purchase_order->load(['items.item.unit', 'supplier', 'creator', 'approver', 'grns' => function ($q) {
            $q->with(['items.item', 'receiver'])->latest('receive_date')->latest('id');
        }]);

        return view('sfl-inventory::admin.purchase-orders.show', ['purchaseOrder' => $purchase_order]);
    }

    public function edit(InvPurchaseOrder $purchase_order): View
    {
        $this->authorize('inv_purchase_order.edit');

        abort_if($purchase_order->status !== 'draft', 403, 'Only draft purchase orders can be edited.');

        $purchase_order->load('items');

        return view('sfl-inventory::admin.purchase-orders.edit', ['purchaseOrder' => $purchase_order] + $this->formOptions());
    }

    public function update(InvPurchaseOrderRequest $request, InvPurchaseOrder $purchase_order): RedirectResponse
    {
        abort_if($purchase_order->status !== 'draft', 403, 'Only draft purchase orders can be edited.');

        $data = $request->validated();

        DB::transaction(function () use ($data, $purchase_order) {
            $total = collect($data['items'])->sum(fn ($line) => $line['quantity'] * $line['rate']);

            $purchase_order->update([
                'supplier_id'   => $data['supplier_id'],
                'order_date'    => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'total_amount'  => $total,
                'remarks'       => $data['remarks'] ?? null,
            ]);

            $purchase_order->items()->delete();
            foreach ($data['items'] as $line) {
                $purchase_order->items()->create([
                    'item_id'  => $line['item_id'],
                    'quantity' => $line['quantity'],
                    'rate'     => $line['rate'],
                    'amount'   => $line['quantity'] * $line['rate'],
                ]);
            }
        });

        return redirect()->route('inventory.purchase-orders.index')->with('success', 'Purchase order updated successfully.');
    }

    public function approve(InvPurchaseOrder $purchase_order): RedirectResponse
    {
        $this->authorize('inv_purchase_order.approve');

        abort_if($purchase_order->status !== 'draft', 403, 'Only draft purchase orders can be approved.');

        $purchase_order->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Purchase order approved successfully.');
    }

    public function destroy(InvPurchaseOrder $purchase_order): RedirectResponse
    {
        $this->authorize('inv_purchase_order.delete');

        if ($purchase_order->isReferenced()) {
            return back()->with('error', 'This purchase order has GRNs against it and cannot be deleted.');
        }

        $purchase_order->delete();

        return back()->with('success', 'Purchase order deleted successfully.');
    }

    private function formOptions(): array
    {
        // Purchase Orders always target the Accessories store — items
        // explicitly assigned to a *different* store are excluded, but an
        // item with no opening store set yet isn't restricted to anywhere
        // (same "unset = unrestricted" rule every other document form's
        // item picker already follows via its data-store JS filter).
        $accessoriesStoreId = InvStore::active()->where('type', 'accessories')->value('id');

        return [
            'suppliers' => InvSupplier::active()->orderBy('name')->get(),
            'items'     => InvItem::active()->with('unit')
                ->when($accessoriesStoreId, fn ($q) => $q->where(fn ($q2) => $q2->whereNull('opening_store_id')->orWhere('opening_store_id', $accessoriesStoreId)))
                ->orderBy('item_name')->get(),
        ];
    }
}
