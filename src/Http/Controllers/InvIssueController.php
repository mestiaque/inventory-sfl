<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvIssueReceiveRequest;
use ME\SflInventory\Http\Requests\InvIssueRequest;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Models\InvDepartment;
use ME\SflInventory\Models\InvGrn;
use ME\SflInventory\Models\InvIssue;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvRequisition;
use ME\SflInventory\Models\InvRequisitionItem;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\InvOperatorScopeService;
use ME\SflInventory\Services\StockService;

class InvIssueController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
        private readonly InvOperatorScopeService $operatorScope,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_issue.list');

        $issues = InvIssue::query()
            ->with(['store', 'toStore', 'department', 'buyer'])
            ->when($request->filled('search'), fn ($q) => $q->where('issue_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('item_id'), fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('item_id', $request->item_id)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('issue_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('issue_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'issued_by'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $departments = InvDepartment::active()->orderBy('name')->get();
        $buyers = InvBuyer::active()->orderBy('name')->get();
        $stores = InvStore::active()->orderBy('name')->get();
        $items = InvItem::active()->orderBy('item_name')->get();

        return view('sfl-inventory::admin.issues.index', compact('issues', 'departments', 'buyers', 'stores', 'items'));
    }

    /**
     * Direct Issue (no requisition) is switched off for now — every issue
     * must be raised against an approved requisition, so this always
     * requires a valid ?requisition_id=.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('inv_issue.add');

        $requisition = InvRequisition::with(['items.item', 'items.color', 'items.size', 'buyer'])
            ->whereIn('status', ['approved', 'partially_issued'])
            ->find($request->requisition_id);

        if (! $requisition) {
            return redirect()->route('inventory.requisitions.index')
                ->with('error', 'Direct issue is disabled — select an approved requisition and click "Issue" against it.');
        }

        return view('sfl-inventory::admin.issues.create', ['requisition' => $requisition] + $this->formOptions());
    }

    /**
     * Creates the Store Delivery Challan and posts stock immediately — the
     * requisition already went through its one approval, so the challan
     * itself no longer needs a separate Authorize/Approve gate. Prepared By
     * -> Authorized By -> Approved By on the printed challan are still
     * populated (all with this same action) so the signature layout is
     * unchanged; authorizeIssue()/approve() below are kept only so any
     * pre-existing pending/authorized challan can still be moved forward.
     */
    public function store(InvIssueRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // A requisition-linked issue inherits its buyer/style/order_ref from
        // the requisition (set once, at the ask step); a direct issue (no
        // requisition) takes them straight from the form.
        $requisition = ! empty($data['requisition_id']) ? InvRequisition::find($data['requisition_id']) : null;

        $buyerId = $requisition->buyer_id ?? $data['buyer_id'] ?? null;
        $style = $requisition->style ?? $data['style'] ?? null;

        // Same inheritance rule as buyer_id/style above — a requisition-linked
        // issue carries the same merch links the requisition was tagged with;
        // a direct issue takes them straight from the form.
        $merStyleId = $requisition->mer_style_id ?? $data['mer_style_id'] ?? null;
        $merSalesContractPoId = $requisition->mer_sales_contract_po_id ?? $data['mer_sales_contract_po_id'] ?? null;
        $merBuyerId = $requisition->mer_buyer_id ?? $data['mer_buyer_id'] ?? null;

        // A style can only be delivered if that same buyer's stock actually
        // has it — i.e. it was posted into the store by a real (posted)
        // Store Receive challan under this buyer. Prevents delivering
        // against a style that was never received at all, or was only
        // received for a different buyer.
        if ($style) {
            $receivedUnderStyle = InvGrn::where('status', 'posted')
                ->where('buyer_id', $buyerId)
                ->where('style', $style)
                ->exists();

            if (! $receivedUnderStyle) {
                throw ValidationException::withMessages([
                    'style' => "Style \"{$style}\" hasn't been received into stock for this buyer yet — check Store Receive (GRN) first.",
                ]);
            }
        }

        // The requisition already carries its one approval, so the challan no
        // longer waits on a separate Authorize/Approve gate — it's created
        // already approved and stock is posted immediately. Only department
        // receipt confirmation stays optional (a real-world hand-off event,
        // not an approval step).
        $autoReceive = $request->boolean('auto_approve') && auth()->user()->can('inv_issue.receive');

        $issue = DB::transaction(function () use ($data, $requisition, $buyerId, $style, $merStyleId, $merSalesContractPoId, $merBuyerId, $autoReceive) {
            $issue = InvIssue::create([
                'requisition_id' => $data['requisition_id'] ?? null,
                'store_id'       => $data['store_id'],
                'to_store_id'    => $data['to_store_id'] ?? null,
                'department_id'  => $data['department_id'],
                'status'         => 'authorized',
                'buyer_id'       => $buyerId,
                'style'          => $style,
                'order_ref'      => $requisition->order_ref ?? $data['order_ref'] ?? null,
                'mer_style_id'              => $merStyleId,
                'mer_sales_contract_po_id'  => $merSalesContractPoId,
                'mer_buyer_id'              => $merBuyerId,
                'issue_date'     => $data['issue_date'],
                'issued_by'      => auth()->id(),
                'remarks'        => $data['remarks'] ?? null,
                'created_by'     => auth()->id(),
                'authorized_by'  => auth()->id(),
                'authorized_at'  => now(),
            ]);

            foreach ($data['items'] as $line) {
                // Color/Size are never re-picked on the Issue — a
                // requisition-linked line inherits its requested variant
                // exactly, so what's issued can never drift from what was
                // actually requested/approved.
                $requisitionItem = ! empty($line['requisition_item_id'])
                    ? InvRequisitionItem::find($line['requisition_item_id'])
                    : null;

                $itemName = ($requisitionItem?->item ?? InvItem::find($line['item_id']))?->item_name ?? "item #{$line['item_id']}";

                // Server-side over-issue guard — the form's max="{{ $due }}"
                // is only a UI hint, never trust it alone. Committed claims
                // on the requisition line (issued_qty) already exclude
                // anything this challan itself hasn't claimed yet, since
                // that only happens below, after this check.
                if ($requisitionItem) {
                    $remaining = (float) $requisitionItem->approved_qty - (float) $requisitionItem->issued_qty;
                    if ((float) $line['issued_qty'] > $remaining + 0.0001) {
                        throw ValidationException::withMessages([
                            'items' => "\"{$itemName}\" — you're trying to issue " . inv_qty($line['issued_qty']) . ', but only ' . inv_qty($remaining) . ' is still approved (unissued) on this requisition line. Lower the quantity, or ask for the requisition to be revised.',
                        ]);
                    }
                }

                // A requisition can approve more than the store actually
                // holds (stock may have moved since approval) — never let
                // an issue claim more than is physically there right now.
                $available = $this->stock->currentStock($line['item_id'], $data['store_id'], $requisitionItem?->color_id, $requisitionItem?->size_id);
                if ((float) $line['issued_qty'] > $available + 0.0001) {
                    $storeName = InvStore::find($data['store_id'])?->name ?? 'this store';
                    $stockMessage = $available > 0
                        ? "only " . inv_qty($available) . " is in stock right now"
                        : "there is no stock left";

                    throw ValidationException::withMessages([
                        'items' => "\"{$itemName}\" — you're trying to issue " . inv_qty($line['issued_qty']) . ", but {$stockMessage} for it at {$storeName}. Lower the quantity, or receive more stock (GRN) into this store before issuing.",
                    ]);
                }

                $issue->items()->create([
                    'requisition_item_id'      => $line['requisition_item_id'] ?? null,
                    'item_id'                  => $line['item_id'],
                    'color_id'                 => $requisitionItem?->color_id,
                    'size_id'                  => $requisitionItem?->size_id,
                    'issued_qty'               => $line['issued_qty'],
                    'unit_rate'                => $line['unit_rate'] ?? 0,
                    'amount'                   => 0,
                    'department_received_qty'  => $autoReceive ? $line['issued_qty'] : 0,
                ]);

                // Committed the moment the challan is prepared — not at final
                // approval — so a second challan against the same requisition
                // line immediately sees the reduced "remaining" balance and
                // can't be raised for material another challan already claims.
                $requisitionItem?->increment('issued_qty', $line['issued_qty']);
            }

            $issue->requisition?->refreshIssueStatus();

            $issue->load('items.item');
            $this->postIssueStock($issue);
            $issue->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);

            if ($autoReceive) {
                $issue->update([
                    'department_receive_status' => 'full',
                    'department_received_by'    => auth()->id(),
                    'department_received_at'    => now(),
                ]);
            }

            return $issue;
        });

        $message = $autoReceive
            ? "Challan {$issue->issue_no} issued and receipt auto-confirmed. Stock updated."
            : "Challan {$issue->issue_no} issued. Stock updated.";

        return redirect()->route('inventory.issues.index')->with('success', $message);
    }

    /**
     * Cancelling only makes sense before the stock-out actually happens —
     * releases the committed requisition quantity back so it can be
     * re-issued (correctly, once — not the source of a double-issue).
     */
    public function cancel(InvIssue $issue): RedirectResponse
    {
        $this->authorize('inv_issue.delete');

        abort_if(! in_array($issue->status, ['pending', 'authorized'], true), 403, 'Only pending or authorized challans can be cancelled — an approved challan has already moved stock.');

        DB::transaction(function () use ($issue) {
            $issue->load('items');

            foreach ($issue->items as $issueItem) {
                if ($issueItem->requisition_item_id) {
                    InvRequisitionItem::find($issueItem->requisition_item_id)?->decrement('issued_qty', $issueItem->issued_qty);
                }
            }

            $issue->requisition?->refreshIssueStatus();
            $issue->delete();
        });

        return redirect()->route('inventory.issues.index')->with('success', "Challan {$issue->issue_no} cancelled and requisition quantity released.");
    }

    public function authorizeIssue(InvIssue $issue): RedirectResponse
    {
        $this->authorize('inv_issue.authorize');

        abort_if($issue->status !== 'pending', 403, 'Only pending challans can be authorized.');

        $issue->update(['status' => 'authorized', 'authorized_by' => auth()->id(), 'authorized_at' => now()]);

        return back()->with('success', "Challan {$issue->issue_no} authorized. Awaiting final approval.");
    }

    /**
     * The real stock-out event: goods physically leave the store here, only
     * after Prepared -> Authorized -> Approved.
     */
    public function approve(InvIssue $issue): RedirectResponse
    {
        $this->authorize('inv_issue.approve');

        abort_if($issue->status !== 'authorized', 403, 'Only authorized challans can be approved.');

        DB::transaction(function () use ($issue) {
            $issue->load('items.item');
            $this->postIssueStock($issue);

            // requisition_item.issued_qty was already committed at store()
            // time (Prepare) — approval only posts the physical stock
            // movement, it doesn't touch the requisition's bookkeeping again.
            $issue->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        });

        return back()->with('success', "Challan {$issue->issue_no} approved and stock updated.");
    }

    /**
     * The real stock-out event, shared by the normal Approve action and the
     * "auto-approve" checkbox on create — goods physically leave the store
     * here. Caller is responsible for the surrounding transaction and for
     * loading $issue->items beforehand.
     */
    private function postIssueStock(InvIssue $issue): void
    {
        foreach ($issue->items as $issueItem) {
            // Re-checked here, not just at store() time — stock can move
            // (another issue, a transfer, an adjustment) in the time
            // between a challan being prepared and actually approved, and
            // this is the moment it truly leaves the store, so it's the
            // last point this can be caught before the ledger goes negative.
            $available = $this->stock->currentStock($issueItem->item_id, $issue->store_id, $issueItem->color_id, $issueItem->size_id);
            if ((float) $issueItem->issued_qty > $available + 0.0001) {
                $itemName = $issueItem->item?->item_name ?? "item #{$issueItem->item_id}";
                throw ValidationException::withMessages([
                    'items' => "Cannot approve this challan: \"{$itemName}\" only has " . inv_qty($available) . ' left in stock, but this challan claims ' . inv_qty($issueItem->issued_qty) . ' — stock has moved since this challan was prepared.',
                ]);
            }

            $outTxn = $this->stock->post([
                'item_id'          => $issueItem->item_id,
                'color_id'         => $issueItem->color_id,
                'size_id'          => $issueItem->size_id,
                'store_id'         => $issue->store_id,
                'transaction_date' => $issue->issue_date,
                'transaction_type' => 'issue',
                'qty_out'          => $issueItem->issued_qty,
                // unit_rate is a decimal-cast attribute, so it comes back as
                // a string like "0.00" — `?:` treats that as truthy (only
                // the literal string "0" is falsy in PHP), so it never fell
                // through to StockService::post()'s moving-average fallback.
                // Every issue ended up posted at rate/value 0. Compare as a
                // number instead.
                'rate'             => ((float) $issueItem->unit_rate) > 0 ? $issueItem->unit_rate : null,
                'department_id'    => $issue->department_id,
                'reference_type'   => 'inv_issue',
                'reference_id'     => $issue->id,
                'remarks'          => "Issue {$issue->issue_no}",
                'created_by'       => $issue->created_by,
            ]);

            $issueItem->update(['unit_rate' => $outTxn->rate, 'amount' => round($issueItem->issued_qty * $outTxn->rate, 2)]);

            // Floor-store destination (Cutting/Sewing/Finishing): post the paired inflow
            // so Production Consumption has a real, non-zero balance to draw down.
            if ($issue->to_store_id) {
                $this->stock->post([
                    'item_id'          => $issueItem->item_id,
                    'color_id'         => $issueItem->color_id,
                    'size_id'          => $issueItem->size_id,
                    'store_id'         => $issue->to_store_id,
                    'transaction_date' => $issue->issue_date,
                    'transaction_type' => 'issue',
                    'qty_in'           => $issueItem->issued_qty,
                    'rate'             => $outTxn->rate,
                    'department_id'    => $issue->department_id,
                    'reference_type'   => 'inv_issue',
                    'reference_id'     => $issue->id,
                    'remarks'          => "Issue {$issue->issue_no}",
                    'created_by'       => $issue->created_by,
                ]);
            }
        }
    }

    public function print(InvIssue $issue): View
    {
        $this->authorize('inv_issue.print');

        $issue->load(['items.item.category', 'items.item.unit', 'items.color', 'items.size', 'store', 'toStore', 'department', 'buyer', 'issuer', 'authorizer', 'approver', 'departmentReceiver', 'requisition.receiver']);

        return view('sfl-inventory::admin.issues.print', compact('issue'));
    }

    public function receiveForm(InvIssue $issue): View
    {
        $this->authorize('inv_issue.receive');

        abort_if($issue->status !== 'approved', 403, 'Only approved (stock-posted) challans can be receipt-confirmed.');

        $issue->load('items.item', 'items.color', 'items.size');

        return view('sfl-inventory::admin.issues.receive', compact('issue'));
    }

    public function receive(InvIssueReceiveRequest $request, InvIssue $issue): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $issue) {
            foreach ($data['items'] as $line) {
                $issue->items()->where('id', $line['id'])->update([
                    'department_received_qty' => $line['department_received_qty'],
                ]);
            }

            $issue->refresh()->load('items');
            $fullyReceived = $issue->items->every(fn ($item) => $item->department_received_qty >= $item->issued_qty);
            $anyReceived = $issue->items->contains(fn ($item) => $item->department_received_qty > 0);

            $issue->update([
                'department_receive_status'  => $fullyReceived ? 'full' : ($anyReceived ? 'partial' : 'pending'),
                'department_received_by'     => auth()->id(),
                'department_received_at'     => now(),
                'department_receive_remarks' => $data['department_receive_remarks'] ?? null,
            ]);
        });

        return redirect()->route('inventory.issues.index')->with('success', "Receipt confirmed for issue {$issue->issue_no}.");
    }

    private function formOptions(): array
    {
        return [
            'stores'      => InvStore::active()->orderBy('name')->get(),
            'departments' => InvDepartment::active()->orderBy('name')->get(),
            'items'       => InvItem::active()->orderBy('item_name')->get(),
            'buyers'      => InvBuyer::active()->orderBy('name')->get(),
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
