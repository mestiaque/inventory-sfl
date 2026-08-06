<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvIssueReceiveRequest;
use ME\SflInventory\Http\Requests\InvIssueRequest;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Models\InvDepartment;
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

        return view('sfl-inventory::admin.issues.index', compact('issues', 'departments', 'buyers', 'stores'));
    }

    /**
     * Direct Issue (no requisition) is switched off for now — every issue
     * must be raised against an approved requisition, so this always
     * requires a valid ?requisition_id=.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('inv_issue.add');

        $requisition = InvRequisition::with(['items.item', 'buyer'])
            ->whereIn('status', ['approved', 'partially_issued'])
            ->find($request->requisition_id);

        if (! $requisition) {
            return redirect()->route('inventory.requisitions.index')
                ->with('error', 'Direct issue is disabled — select an approved requisition and click "Issue" against it.');
        }

        return view('sfl-inventory::admin.issues.create', ['requisition' => $requisition] + $this->formOptions());
    }

    /**
     * Creates the Store Delivery Challan as a draft — no stock movement yet.
     * Goods only leave the store once the challan is authorized and approved
     * (see authorizeIssue()/approve() below), matching the printed challan's
     * Prepared By -> Authorized By -> Approved By signature chain.
     */
    public function store(InvIssueRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // A requisition-linked issue inherits its buyer/style/order_ref from
        // the requisition (set once, at the ask step); a direct issue (no
        // requisition) takes them straight from the form.
        $requisition = ! empty($data['requisition_id']) ? InvRequisition::find($data['requisition_id']) : null;

        $issue = DB::transaction(function () use ($data, $requisition) {
            $issue = InvIssue::create([
                'requisition_id' => $data['requisition_id'] ?? null,
                'store_id'       => $data['store_id'],
                'to_store_id'    => $data['to_store_id'] ?? null,
                'department_id'  => $data['department_id'],
                'status'         => 'pending',
                'buyer_id'       => $requisition->buyer_id ?? $data['buyer_id'] ?? null,
                'style'          => $requisition->style ?? $data['style'] ?? null,
                'order_ref'      => $requisition->order_ref ?? $data['order_ref'] ?? null,
                'issue_date'     => $data['issue_date'],
                'issued_by'      => auth()->id(),
                'remarks'        => $data['remarks'] ?? null,
                'created_by'     => auth()->id(),
            ]);

            foreach ($data['items'] as $line) {
                $issue->items()->create([
                    'requisition_item_id' => $line['requisition_item_id'] ?? null,
                    'item_id'             => $line['item_id'],
                    'issued_qty'          => $line['issued_qty'],
                    'unit_rate'           => $line['unit_rate'] ?? 0,
                    'amount'              => 0,
                ]);

                // Committed the moment the challan is prepared — not at final
                // approval — so a second challan against the same requisition
                // line immediately sees the reduced "remaining" balance and
                // can't be raised for material another challan already claims.
                if (! empty($line['requisition_item_id'])) {
                    InvRequisitionItem::find($line['requisition_item_id'])?->increment('issued_qty', $line['issued_qty']);
                }
            }

            $issue->requisition?->refreshIssueStatus();

            return $issue;
        });

        return redirect()->route('inventory.issues.index')->with('success', "Challan {$issue->issue_no} prepared. Awaiting authorization.");
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
            $issue->load('items');

            foreach ($issue->items as $issueItem) {
                $outTxn = $this->stock->post([
                    'item_id'          => $issueItem->item_id,
                    'store_id'         => $issue->store_id,
                    'transaction_date' => $issue->issue_date,
                    'transaction_type' => 'issue',
                    'qty_out'          => $issueItem->issued_qty,
                    'rate'             => $issueItem->unit_rate ?: null,
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

            // requisition_item.issued_qty was already committed at store()
            // time (Prepare) — approval only posts the physical stock
            // movement, it doesn't touch the requisition's bookkeeping again.
            $issue->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        });

        return back()->with('success', "Challan {$issue->issue_no} approved and stock updated.");
    }

    public function print(InvIssue $issue): View
    {
        $this->authorize('inv_issue.print');

        $issue->load(['items.item.category', 'items.item.unit', 'store', 'toStore', 'department', 'buyer', 'issuer', 'authorizer', 'approver', 'departmentReceiver', 'requisition.receiver']);

        return view('sfl-inventory::admin.issues.print', compact('issue'));
    }

    public function receiveForm(InvIssue $issue): View
    {
        $this->authorize('inv_issue.receive');

        abort_if($issue->status !== 'approved', 403, 'Only approved (stock-posted) challans can be receipt-confirmed.');

        $issue->load('items.item');

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
        ];
    }
}
