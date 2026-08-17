<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use ME\SflInventory\Http\Requests\InvGrnRequest;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Models\InvGrn;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvPurchaseOrder;
use ME\SflInventory\Models\InvPurchaseOrderItem;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Models\InvSupplier;
use ME\SflInventory\Services\InvOperatorScopeService;
use ME\SflInventory\Services\StockService;

class InvGrnController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
        private readonly InvOperatorScopeService $operatorScope,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_grn.list');

        $grns = InvGrn::query()
            ->with(['store', 'supplier', 'buyer', 'purchaseOrder', 'creator'])
            ->withCount('items')
            ->when($request->filled('search'), fn ($q) => $q->where('grn_number', 'like', '%' . $request->search . '%'))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('source_type'), fn ($q) => $q->where('source_type', $request->source_type))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('receive_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('receive_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'created_by'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stores = InvStore::active()->orderBy('name')->get();
        $suppliers = InvSupplier::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.grns.index', compact('grns', 'stores', 'suppliers'));
    }

    /**
     * Chooser page — a challan is either against a Purchase Order (from a
     * supplier) or Buyer Supplied (fabric/accessories the buyer sends
     * directly, no purchase involved). Kept as two separate forms so each
     * only shows the fields relevant to that source.
     */
    public function create(): View
    {
        $this->authorize('inv_grn.add');

        return view('sfl-inventory::admin.grns.create');
    }

    public function createPurchase(Request $request): View
    {
        $this->authorize('inv_grn.add');

        $purchaseOrder = null;
        if ($request->filled('purchase_order_id')) {
            $purchaseOrder = InvPurchaseOrder::with('items.item')
                ->selectableForGrn()
                ->find($request->purchase_order_id);
        }

        return view('sfl-inventory::admin.grns.create-purchase', [
            'purchaseOrder' => $purchaseOrder,
        ] + $this->formOptions());
    }

    public function createBuyer(): View
    {
        $this->authorize('inv_grn.add');

        return view('sfl-inventory::admin.grns.create-buyer', $this->formOptions());
    }

    public function show(InvGrn $grn): View
    {
        $this->authorize('inv_grn.view');

        $grn->load(['store', 'supplier', 'buyer', 'purchaseOrder', 'creator', 'receiver', 'items.item.unit', 'items.purchaseOrderItem']);

        return view('sfl-inventory::admin.grns.show', ['grn' => $grn]);
    }

    /**
     * Store/source/supplier/buyer/PO link are fixed on edit — changing which
     * store or which order a GRN belongs to is a bigger operation than a
     * quantity/rate correction, so that stays a delete + re-create. Only the
     * line quantities/rates and the header's descriptive fields are editable.
     */
    public function edit(InvGrn $grn): View
    {
        $this->authorize('inv_grn.edit');

        $grn->load(['items.item.unit', 'items.purchaseOrderItem', 'purchaseOrder.items.item']);

        $view = $grn->source_type === 'buyer_supplied' ? 'edit-buyer' : 'edit-purchase';

        return view("sfl-inventory::admin.grns.{$view}", ['grn' => $grn] + $this->formOptions());
    }

    public function update(InvGrnRequest $request, InvGrn $grn): RedirectResponse
    {
        $data = $request->validated();

        $grn->load('items');
        $this->assertReversible($grn, $grn->items);

        DB::transaction(function () use ($data, $grn) {
            $this->reverseGrnItems($grn, $grn->items);
            $grn->items()->delete();

            $total = collect($data['items'])->sum(fn ($line) => $line['received_qty'] * $line['rate']);

            $grn->update([
                'style'              => $data['style'] ?? null,
                'order_ref'          => $data['order_ref'] ?? null,
                'challan_invoice_no' => $data['challan_invoice_no'] ?? null,
                'receive_date'       => $data['receive_date'],
                'received_by'        => $data['received_by'] ?? null,
                'total_amount'       => $total,
                'remarks'            => $data['remarks'] ?? null,
            ]);

            foreach ($data['items'] as $line) {
                $grnItem = $grn->items()->create([
                    'purchase_order_item_id' => $line['purchase_order_item_id'] ?? null,
                    'item_id'                => $line['item_id'],
                    'ordered_qty'            => $line['ordered_qty'] ?? 0,
                    'received_qty'           => $line['received_qty'],
                    'rejected_qty'           => $line['rejected_qty'] ?? 0,
                    'rate'                   => $line['rate'],
                    'amount'                 => $line['received_qty'] * $line['rate'],
                    'lot_no'                 => $line['lot_no'] ?? null,
                    'batch_no'               => $line['batch_no'] ?? null,
                ]);

                $this->stock->post([
                    'item_id'          => $grnItem->item_id,
                    'store_id'         => $grn->store_id,
                    'transaction_date' => $grn->receive_date,
                    'transaction_type' => 'grn',
                    'qty_in'           => $grnItem->received_qty,
                    'rate'             => $grnItem->rate,
                    'reference_type'   => 'inv_grn',
                    'reference_id'     => $grn->id,
                    'remarks'          => "GRN {$grn->grn_number} (edited)",
                    'created_by'       => auth()->id(),
                ]);

                if ($grnItem->purchase_order_item_id) {
                    InvPurchaseOrderItem::find($grnItem->purchase_order_item_id)?->increment('received_qty', $grnItem->received_qty);
                }
            }

            $grn->purchaseOrder?->refreshReceiptStatus();
        });

        return redirect()->route('inventory.grns.index')->with('success', "GRN {$grn->grn_number} updated and stock re-posted.");
    }

    public function store(InvGrnRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $grn = DB::transaction(function () use ($data) {
            $total = collect($data['items'])->sum(fn ($line) => $line['received_qty'] * $line['rate']);

            $grn = InvGrn::create([
                'purchase_order_id'  => $data['purchase_order_id'] ?? null,
                'source_type'        => $data['source_type'],
                'store_id'           => $data['store_id'],
                'supplier_id'        => $data['source_type'] === 'purchase' ? $data['supplier_id'] : null,
                'buyer_id'           => $data['source_type'] === 'buyer_supplied' ? $data['buyer_id'] : null,
                'style'              => $data['style'] ?? null,
                'order_ref'          => $data['order_ref'] ?? null,
                'challan_invoice_no' => $data['challan_invoice_no'] ?? null,
                'receive_date'       => $data['receive_date'],
                'received_by'        => $data['received_by'] ?? null,
                'status'             => 'posted',
                'total_amount'       => $total,
                'remarks'            => $data['remarks'] ?? null,
                'created_by'         => auth()->id(),
            ]);

            foreach ($data['items'] as $line) {
                $grnItem = $grn->items()->create([
                    'purchase_order_item_id' => $line['purchase_order_item_id'] ?? null,
                    'item_id'                => $line['item_id'],
                    'ordered_qty'            => $line['ordered_qty'] ?? 0,
                    'received_qty'           => $line['received_qty'],
                    'rejected_qty'           => $line['rejected_qty'] ?? 0,
                    'rate'                   => $line['rate'],
                    'amount'                 => $line['received_qty'] * $line['rate'],
                    'lot_no'                 => $line['lot_no'] ?? null,
                    'batch_no'               => $line['batch_no'] ?? null,
                ]);

                $this->stock->post([
                    'item_id'          => $grnItem->item_id,
                    'store_id'         => $grn->store_id,
                    'transaction_date' => $grn->receive_date,
                    'transaction_type' => 'grn',
                    'qty_in'           => $grnItem->received_qty,
                    'rate'             => $grnItem->rate,
                    'reference_type'   => 'inv_grn',
                    'reference_id'     => $grn->id,
                    'remarks'          => "GRN {$grn->grn_number}",
                    'created_by'       => $grn->created_by,
                ]);

                if ($grnItem->purchase_order_item_id) {
                    $poItem = InvPurchaseOrderItem::find($grnItem->purchase_order_item_id);
                    $poItem?->increment('received_qty', $grnItem->received_qty);
                }
            }

            $grn->purchaseOrder?->refreshReceiptStatus();

            return $grn;
        });

        return redirect()->route('inventory.grns.index')->with('success', "GRN {$grn->grn_number} posted and stock updated.");
    }

    public function destroy(InvGrn $grn): RedirectResponse
    {
        $this->authorize('inv_grn.delete');

        $grn->load('items');

        try {
            $this->assertReversible($grn, $grn->items);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        DB::transaction(function () use ($grn) {
            $this->reverseGrnItems($grn, $grn->items);
            $grn->purchaseOrder?->refreshReceiptStatus();
            $grn->delete();
        });

        return redirect()->route('inventory.grns.index')->with('success', "GRN {$grn->grn_number} deleted and stock reversed.");
    }

    /**
     * A GRN's received stock may already have been issued/transferred/
     * consumed elsewhere by the time someone tries to edit or delete it —
     * reversing it then would push that item's stock negative. Block that
     * up front with a clear message instead of leaving a half-reversed GRN.
     */
    private function assertReversible(InvGrn $grn, $items): void
    {
        foreach ($items as $grnItem) {
            $available = $this->stock->currentStock($grnItem->item_id, $grn->store_id);
            if ($available < $grnItem->received_qty) {
                $itemName = $grnItem->item?->item_name ?? "item #{$grnItem->item_id}";
                throw ValidationException::withMessages([
                    'items' => "Cannot change this GRN: \"{$itemName}\" only has " . inv_qty($available) . ' left in stock, but this GRN brought in ' . inv_qty($grnItem->received_qty) . ' — some has already been issued/used elsewhere. Post a Stock Adjustment instead.',
                ]);
            }
        }
    }

    private function reverseGrnItems(InvGrn $grn, $items): void
    {
        foreach ($items as $grnItem) {
            $this->stock->post([
                'item_id'          => $grnItem->item_id,
                'store_id'         => $grn->store_id,
                'transaction_date' => now()->toDateString(),
                'transaction_type' => 'grn_reversal',
                'qty_out'          => $grnItem->received_qty,
                'rate'             => $grnItem->rate,
                'reference_type'   => 'inv_grn',
                'reference_id'     => $grn->id,
                'remarks'          => "Reversal of GRN {$grn->grn_number}",
                'created_by'       => auth()->id(),
            ]);

            if ($grnItem->purchase_order_item_id) {
                InvPurchaseOrderItem::find($grnItem->purchase_order_item_id)?->decrement('received_qty', $grnItem->received_qty);
            }
        }
    }

    private function formOptions(): array
    {
        return [
            'stores'          => InvStore::active()->orderBy('name')->get(),
            'accessoriesStore' => InvStore::active()->where('type', 'accessories')->first(),
            'buyerStore'      => InvStore::active()->where('type', 'raw_material')->first(),
            'suppliers'       => InvSupplier::active()->orderBy('name')->get(),
            'buyers'          => InvBuyer::active()->orderBy('name')->get(),
            'items'           => InvItem::active()->with('unit')->orderBy('item_name')->get(),
            'users'           => \App\Models\User::orderBy('name')->get(),
        ];
    }
}
