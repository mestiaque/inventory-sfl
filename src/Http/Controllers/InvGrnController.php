<?php

namespace ME\SflInventory\Http\Controllers;

use App\Models\Approval;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use ME\SflInventory\Http\Requests\InvGrnApprovalRequest;
use ME\SflInventory\Http\Requests\InvGrnRequest;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Models\InvColor;
use ME\SflInventory\Models\InvGrn;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvSize;
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
            ->when($request->filled('item_id'), fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('item_id', $request->item_id)))
            ->when($request->filled('source_type'), fn ($q) => $q->where('source_type', $request->source_type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('receive_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('receive_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'created_by'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stores = InvStore::active()->orderBy('name')->get();
        $suppliers = InvSupplier::active()->orderBy('name')->get();
        $items = InvItem::active()->orderBy('item_name')->get();

        $trashedGrns = InvGrn::onlyTrashed()->with('supplier')->latest('deleted_at')->get()
            ->map(fn ($grn) => ['id' => $grn->id, 'title' => $grn->grn_number, 'subtitle' => $grn->supplier?->name, 'deleted_at' => $grn->deleted_at]);

        return view('sfl-inventory::admin.grns.index', compact('grns', 'stores', 'suppliers', 'items', 'trashedGrns'));
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

    /**
     * Direct/manual purchase receiving (no Store Order) is disabled — every
     * purchase challan must be raised against an approved/received Store
     * Order, mirroring how Store Order creation requires an approved
     * Purchase Requisition and Issue requires an approved Requisition.
     * No ?purchase_order_id= yet: stay on this same page and show a picker
     * instead of bouncing to the Store Order list — picking one just
     * reloads this page with the id set.
     */
    public function createPurchase(Request $request): View
    {
        $this->authorize('inv_grn.add');

        $purchaseOrder = InvPurchaseOrder::with('items.item', 'items.color', 'items.size')
            ->selectableForGrn()
            ->find($request->purchase_order_id);

        return view('sfl-inventory::admin.grns.create-purchase', [
            'purchaseOrder'  => $purchaseOrder,
            'purchaseOrders' => $purchaseOrder ? collect() : InvPurchaseOrder::with('supplier')->selectableForGrn()->orderByDesc('id')->get(),
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

        $grn->load(['store', 'supplier', 'buyer', 'purchaseOrder', 'creator', 'approver', 'receiver', 'items.item.unit', 'items.color', 'items.size', 'items.purchaseOrderItem']);

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

        abort_if($grn->status === 'rejected', 403, 'A rejected GRN cannot be edited — create a new challan instead.');

        $grn->load(['items.item.unit', 'items.color', 'items.size', 'items.purchaseOrderItem', 'purchaseOrder.items.item']);

        $view = $grn->source_type === 'buyer_supplied' ? 'edit-buyer' : 'edit-purchase';

        return view("sfl-inventory::admin.grns.{$view}", ['grn' => $grn] + $this->formOptions());
    }

    public function update(InvGrnRequest $request, InvGrn $grn): RedirectResponse
    {
        abort_if($grn->status === 'rejected', 403, 'A rejected GRN cannot be edited — create a new challan instead.');

        $data = $request->validated();

        $grn->load('items');
        $wasPosted = $grn->status === 'posted';

        if ($wasPosted) {
            $this->assertReversible($grn, $grn->items);
        }

        DB::transaction(function () use ($data, $grn, $wasPosted) {
            if ($wasPosted) {
                $this->reverseGrnItems($grn, $grn->items);
            } else {
                // Still pending Receive Approval — nothing has posted to the
                // ledger yet, so there's nothing to reverse, only the
                // Purchase Order quantity "claim" made at store() time.
                $this->releasePurchaseOrderClaims($grn->items);
            }
            $grn->items()->delete();

            $total = collect($data['items'])->sum(fn ($line) => $line['received_qty'] * $line['rate']);

            $grn->update([
                'style'              => $data['style'] ?? null,
                'order_ref'          => $data['order_ref'] ?? null,
                'mer_style_id'              => $data['mer_style_id'] ?? null,
                'mer_sales_contract_po_id'  => $data['mer_sales_contract_po_id'] ?? null,
                'mer_buyer_id'              => $data['mer_buyer_id'] ?? null,
                'challan_invoice_no' => $data['challan_invoice_no'] ?? null,
                'receive_date'       => $data['receive_date'],
                'received_by'        => $data['received_by'] ?? null,
                'total_amount'       => $total,
                'remarks'            => $data['remarks'] ?? null,
            ]);

            $this->createGrnItems($grn, $data['items'], $wasPosted, ' (edited)');

            $grn->purchaseOrder?->refreshReceiptStatus();
        });

        $message = $wasPosted
            ? "GRN {$grn->grn_number} updated and stock re-posted."
            : "GRN {$grn->grn_number} updated — still pending receive approval.";

        return redirect()->route('inventory.grns.index')->with('success', $message);
    }

    public function store(InvGrnRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Buyer-supplied challans keep posting immediately — no approval
        // step, matching "Receive from Buyer (Direct Goods Receive)". A
        // purchase challan is auto-approved only if its preparer also has
        // Receive Approval rights and opted into the shortcut; otherwise it
        // waits as 'pending' for someone to approve/reject it.
        $autoApprove = $data['source_type'] === 'buyer_supplied'
            || ($request->boolean('auto_approve') && auth()->user()->can('inv_grn.approve'));

        $grn = DB::transaction(function () use ($data, $autoApprove) {
            $total = collect($data['items'])->sum(fn ($line) => $line['received_qty'] * $line['rate']);

            $grn = InvGrn::create([
                'purchase_order_id'  => $data['purchase_order_id'] ?? null,
                'source_type'        => $data['source_type'],
                'store_id'           => $data['store_id'],
                'supplier_id'        => $data['source_type'] === 'purchase' ? $data['supplier_id'] : null,
                'buyer_id'           => $data['source_type'] === 'buyer_supplied' ? $data['buyer_id'] : null,
                'style'              => $data['style'] ?? null,
                'order_ref'          => $data['order_ref'] ?? null,
                'mer_style_id'              => $data['mer_style_id'] ?? null,
                'mer_sales_contract_po_id'  => $data['mer_sales_contract_po_id'] ?? null,
                'mer_buyer_id'              => $data['mer_buyer_id'] ?? null,
                'challan_invoice_no' => $data['challan_invoice_no'] ?? null,
                'receive_date'       => $data['receive_date'],
                'received_by'        => $data['received_by'] ?? null,
                'status'             => $autoApprove ? 'posted' : 'pending',
                'approved_by'        => $autoApprove ? auth()->id() : null,
                'approved_at'        => $autoApprove ? now() : null,
                'total_amount'       => $total,
                'remarks'            => $data['remarks'] ?? null,
                'created_by'         => auth()->id(),
            ]);

            // The PO-item "claim" (received_qty) is committed the moment the
            // challan is prepared, not at final approval — same precedent as
            // InvIssueController::store() committing issued_qty at Prepared
            // time. Otherwise two concurrent pending-approval GRNs against
            // the same PO line could both claim its remaining quantity. Only
            // the actual ledger post (real stock truth) waits for approval.
            $this->createGrnItems($grn, $data['items'], $autoApprove, '', $grn->created_by);

            // A Store Order is auto-created (on Purchase Requisition
            // approval) with no supplier yet — the first challan received
            // against it is the one that actually names the supplier, so
            // backfill it here rather than leaving it null forever. Only
            // fills a still-empty value — never overwrites one already set.
            if ($grn->source_type === 'purchase' && $grn->purchaseOrder && ! $grn->purchaseOrder->supplier_id) {
                $grn->purchaseOrder->update(['supplier_id' => $grn->supplier_id]);
            }

            $grn->purchaseOrder?->refreshReceiptStatus();

            return $grn;
        });

        if (! $autoApprove) {
            app(ApprovalService::class)->request([
                'module'       => 'inventory.grn_receive',
                'approvable'   => $grn,
                'title'        => "GRN Receive Approval - {$grn->grn_number}",
                'description'  => "{$grn->creator?->name} received a challan" . ($grn->supplier ? " from {$grn->supplier->name}" : '') . '.',
                'route_name'   => 'inventory.grns.approval-form',
                'route_params' => ['grn' => $grn->id],
                'requested_by' => auth()->id(),
            ]);

            return redirect()->route('inventory.grns.index')->with('success', "GRN {$grn->grn_number} submitted for receive approval.");
        }

        return redirect()->route('inventory.grns.index')->with('success', "GRN {$grn->grn_number} posted and stock updated.");
    }

    /**
     * Dedicated Receive Approval page for a pending purchase GRN — mirrors
     * InvPurchaseRequisitionController::approvalForm()/approval(). Buyer
     * Supplied GRNs never reach 'pending', so there's nothing to approve
     * there.
     */
    public function approvalForm(InvGrn $grn): View
    {
        $this->authorize('inv_grn.approve');

        abort_if($grn->source_type !== 'purchase' || $grn->status !== 'pending', 403, 'Only a pending purchase GRN can be approved or rejected.');

        $grn->load(['items.item.unit', 'items.color', 'items.size', 'store', 'supplier', 'purchaseOrder', 'creator']);

        return view('sfl-inventory::admin.grns.approve', ['grn' => $grn]);
    }

    public function approval(InvGrnApprovalRequest $request, InvGrn $grn): RedirectResponse
    {
        abort_if($grn->source_type !== 'purchase' || $grn->status !== 'pending', 403, 'Only a pending purchase GRN can be approved or rejected.');

        $data = $request->validated();
        $grn->load('items');

        if ($data['decision'] === 'reject') {
            DB::transaction(function () use ($grn, $data) {
                $this->releasePurchaseOrderClaims($grn->items);
                $grn->purchaseOrder?->refreshReceiptStatus();

                $grn->update([
                    'status'           => 'rejected',
                    'approved_by'      => auth()->id(),
                    'approved_at'      => now(),
                    'approval_remarks' => $data['approval_remarks'] ?? null,
                ]);
            });

            $this->syncCentralApproval($grn, 'rejected', $data['approval_remarks'] ?? null);

            return redirect()->route('inventory.grns.index')->with('success', "GRN {$grn->grn_number} rejected and store order quantity released.");
        }

        DB::transaction(function () use ($grn, $data) {
            foreach ($grn->items as $grnItem) {
                $this->stock->post([
                    'item_id'          => $grnItem->item_id,
                    'color_id'         => $grnItem->color_id,
                    'size_id'          => $grnItem->size_id,
                    'store_id'         => $grn->store_id,
                    'transaction_date' => $grn->receive_date,
                    'transaction_type' => 'grn',
                    'qty_in'           => $grnItem->received_qty,
                    'rate'             => $grnItem->rate,
                    'reference_type'   => 'inv_grn',
                    'reference_id'     => $grn->id,
                    'remarks'          => "GRN {$grn->grn_number} (approved)",
                    'created_by'       => $grn->created_by,
                ]);
            }

            $grn->update([
                'status'           => 'posted',
                'approved_by'      => auth()->id(),
                'approved_at'      => now(),
                'approval_remarks' => $data['approval_remarks'] ?? null,
            ]);
        });

        $this->syncCentralApproval($grn, 'approved', $data['approval_remarks'] ?? null);

        return redirect()->route('inventory.grns.index')->with('success', "GRN {$grn->grn_number} approved and stock updated.");
    }

    public function destroy(InvGrn $grn): RedirectResponse
    {
        $this->authorize('inv_grn.delete');

        $grn->load('items');

        if ($grn->status === 'posted') {
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

        // 'pending': nothing posted yet, only the PO claim needs releasing.
        // 'rejected': the PO claim was already released when it was rejected.
        DB::transaction(function () use ($grn) {
            if ($grn->status === 'pending') {
                $this->releasePurchaseOrderClaims($grn->items);
                $grn->purchaseOrder?->refreshReceiptStatus();
            }
            $grn->delete();
        });

        return redirect()->route('inventory.grns.index')->with('success', "GRN {$grn->grn_number} deleted.");
    }

    public function restore(InvGrn $grn): RedirectResponse
    {
        $this->authorize('inv_grn.delete');

        $grn->restore();

        return back()->with('success', 'GRN restored successfully.');
    }

    /**
     * Stock was already reversed when the GRN was soft-deleted, so this just
     * removes the record — its line items are deleted first since this
     * database's declared FK cascades aren't reliably enforced (see
     * InvItemController::forceDestroy).
     */
    public function forceDestroy(InvGrn $grn): RedirectResponse
    {
        $this->authorize('inv_grn.force_delete');

        DB::transaction(function () use ($grn) {
            DB::table('inv_grn_items')->where('grn_id', $grn->id)->delete();
            $grn->forceDelete();
        });

        return back()->with('success', 'GRN permanently deleted.');
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
            $available = $this->stock->currentStock($grnItem->item_id, $grn->store_id, $grnItem->color_id, $grnItem->size_id);
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
                'color_id'         => $grnItem->color_id,
                'size_id'          => $grnItem->size_id,
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

    /**
     * Releases the Purchase Order quantity "claim" made at store() time
     * without touching the stock ledger — used when a GRN never actually
     * posted (still 'pending' Receive Approval, or being rejected).
     */
    private function releasePurchaseOrderClaims($items): void
    {
        foreach ($items as $grnItem) {
            if ($grnItem->purchase_order_item_id) {
                InvPurchaseOrderItem::find($grnItem->purchase_order_item_id)?->decrement('received_qty', $grnItem->received_qty);
            }
        }
    }

    /**
     * (Re)creates a GRN's line items. The Purchase Order "claim" is always
     * committed immediately (see the note in store()); the ledger post only
     * happens when $postToLedger is true (the GRN is 'posted', not still
     * waiting on Receive Approval).
     */
    private function createGrnItems(InvGrn $grn, array $lines, bool $postToLedger, string $remarksSuffix = '', ?int $ledgerCreatedBy = null): void
    {
        foreach ($lines as $line) {
            // A PO-linked line's variant is inherited from the order line
            // (never re-picked), and a plain item that already has its own
            // fixed color/size just carries that — only a "generic"
            // multi-variant item, received with no PO to inherit from, needs
            // the picked items[].color_id/size_id from the form.
            $poItemVariant = ! empty($line['purchase_order_item_id'])
                ? InvPurchaseOrderItem::find($line['purchase_order_item_id'])
                : null;
            $item = $poItemVariant?->item ?? InvItem::find($line['item_id']);
            $colorId = $poItemVariant?->color_id ?? $item?->color_id ?? $line['color_id'] ?? null;
            $sizeId = $poItemVariant?->size_id ?? $item?->size_id ?? $line['size_id'] ?? null;

            // Server-side over-receive guard — the form's max="{{ $due }}"
            // is only a UI hint, never trust it alone. By the time this
            // runs, any prior claim this same GRN held on the line has
            // already been released (see update()'s branches above), so
            // $poItemVariant->received_qty here already excludes it —
            // comparing against it directly is correct for both create and
            // edit.
            if ($poItemVariant) {
                $due = (float) $poItemVariant->quantity - (float) $poItemVariant->received_qty;
                if ((float) $line['received_qty'] > $due + 0.0001) {
                    throw ValidationException::withMessages([
                        'items' => "Cannot receive " . inv_qty($line['received_qty']) . ' of "' . ($item?->item_name ?? "item #{$line['item_id']}") . '" — only ' . inv_qty($due) . ' is still due on this Store Order line.',
                    ]);
                }
            }

            $grnItem = $grn->items()->create([
                'purchase_order_item_id' => $line['purchase_order_item_id'] ?? null,
                'item_id'                => $line['item_id'],
                'color_id'               => $colorId,
                'size_id'                => $sizeId,
                'ordered_qty'            => $line['ordered_qty'] ?? 0,
                'received_qty'           => $line['received_qty'],
                'rejected_qty'           => $line['rejected_qty'] ?? 0,
                'rate'                   => $line['rate'],
                'amount'                 => $line['received_qty'] * $line['rate'],
                'lot_no'                 => $line['lot_no'] ?? null,
                'batch_no'               => $line['batch_no'] ?? null,
                'expiry_date'            => $line['expiry_date'] ?? null,
            ]);

            if ($postToLedger) {
                $this->stock->post([
                    'item_id'          => $grnItem->item_id,
                    'color_id'         => $grnItem->color_id,
                    'size_id'          => $grnItem->size_id,
                    'store_id'         => $grn->store_id,
                    'transaction_date' => $grn->receive_date,
                    'transaction_type' => 'grn',
                    'qty_in'           => $grnItem->received_qty,
                    'rate'             => $grnItem->rate,
                    'reference_type'   => 'inv_grn',
                    'reference_id'     => $grn->id,
                    'remarks'          => "GRN {$grn->grn_number}{$remarksSuffix}",
                    'created_by'       => $ledgerCreatedBy ?? auth()->id(),
                ]);
            }

            if ($grnItem->purchase_order_item_id) {
                InvPurchaseOrderItem::find($grnItem->purchase_order_item_id)?->increment('received_qty', $grnItem->received_qty);
            }
        }
    }

    /**
     * Mirrors the decision made on this dedicated approval page back onto
     * the central Approvals record — see
     * InvPurchaseRequisitionController::syncCentralApproval() for the same
     * pattern and rationale.
     */
    private function syncCentralApproval(InvGrn $grn, string $status, ?string $remarks): void
    {
        Approval::where('module', 'inventory.grn_receive')
            ->where('approvable_type', InvGrn::class)
            ->where('approvable_id', $grn->id)
            ->where('status', 'pending')
            ->update([
                'status'      => $status,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'remarks'     => $remarks,
            ]);
    }

    private function formOptions(): array
    {
        $employees = class_exists(\ME\Hr\Models\HrEmployee::class)
            ? \ME\Hr\Models\HrEmployee::query()->where('status', 1)->orderBy('name')->get()
            : collect();

        return [
            'stores'          => InvStore::active()->orderBy('name')->get(),
            'accessoriesStore' => InvStore::active()->where('type', 'accessories')->first(),
            'buyerStore'      => InvStore::active()->where('type', 'raw_material')->first(),
            'suppliers'       => InvSupplier::active()->orderBy('name')->get(),
            'buyers'          => InvBuyer::active()->orderBy('name')->get(),
            'items'           => InvItem::active()->with('unit')->orderBy('item_name')->get(),
            'colors'          => InvColor::active()->orderBy('name')->get(),
            'sizes'           => InvSize::active()->ordered()->get(),
            'employees'       => $employees,
            'merStylesOptions' => class_exists(\ME\MerchandisingTrace\Models\Style::class)
                ? \ME\MerchandisingTrace\Models\Style::query()->orderBy('style_no')->get(['id', 'style_no', 'name'])
                : collect(),
            'merSalesContractPosOptions' => class_exists(\ME\MerchandisingTrace\Models\SalesContractPo::class)
                ? \ME\MerchandisingTrace\Models\SalesContractPo::query()->latest('id')->limit(500)->get(['id', 'po_no'])
                : collect(),
            'merBuyersOptions' => class_exists(\ME\MerchandisingTrace\Models\Buyer::class)
                ? \ME\MerchandisingTrace\Models\Buyer::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ];
    }
}
