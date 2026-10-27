<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvIssueReturnRequest;
use ME\SflInventory\Models\InvIssue;
use ME\SflInventory\Models\InvIssueReturn;
use ME\SflInventory\Models\InvIssueReturnItem;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\InvOperatorScopeService;
use ME\SflInventory\Services\StockService;

/**
 * Store Issue Return: a department brings back goods it took on an issue
 * challan but no longer needs. Stock goes back into the store it was issued
 * from, at the rate it left at. The requisition is not reopened — the goods
 * came back because they weren't needed.
 */
class InvIssueReturnController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
        private readonly InvOperatorScopeService $operatorScope,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_issue.return');

        $returns = InvIssueReturn::query()
            ->with(['issue.department', 'store', 'returner'])
            ->withSum('items as returned_total', 'returned_qty')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('return_no', 'like', '%' . $request->search . '%')
                ->orWhereHas('issue', fn ($iq) => $iq->where('issue_no', 'like', '%' . $request->search . '%'))))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('return_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('return_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'created_by'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.issue-returns.index', compact('returns', 'stores'));
    }

    public function create(InvIssue $issue): View|RedirectResponse
    {
        $this->authorize('inv_issue.return');

        if ($issue->status !== 'approved') {
            return redirect()->route('inventory.issues.index')->with('error', "Challan {$issue->issue_no} hasn't moved stock yet — cancel it instead of returning.");
        }

        $issue->load(['items.item.unit', 'items.color', 'items.size', 'store', 'toStore', 'department']);
        $returned = $this->returnedByLine($issue);

        if ($issue->items->every(fn ($line) => (float) $line->issued_qty - ($returned[$line->id] ?? 0) <= 0.0001)) {
            return redirect()->route('inventory.issues.index')->with('error', "Everything on challan {$issue->issue_no} has already been returned.");
        }

        return view('sfl-inventory::admin.issue-returns.create', compact('issue', 'returned'));
    }

    public function store(InvIssueReturnRequest $request, InvIssue $issue): RedirectResponse
    {
        abort_if($issue->status !== 'approved', 403, 'Only approved (stock-posted) challans can be returned.');

        $data = $request->validated();
        $lines = collect($data['items'])->filter(fn ($line) => (float) ($line['returned_qty'] ?? 0) > 0);

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Enter a return quantity on at least one line.']);
        }

        $return = DB::transaction(function () use ($issue, $data, $lines) {
            // One return at a time per challan, so two can't both pass the "left to return" check.
            InvIssue::whereKey($issue->id)->lockForUpdate()->first();
            $issue->load('items.item', 'toStore');
            $issueLines = $issue->items->keyBy('id');
            $returned = $this->returnedByLine($issue);

            $return = InvIssueReturn::create([
                'issue_id'    => $issue->id,
                'store_id'    => $issue->store_id,
                'return_date' => $data['return_date'],
                'reason'      => $data['reason'],
                'returned_by' => auth()->id(),
                'created_by'  => auth()->id(),
            ]);

            foreach ($lines as $line) {
                $issueLine = $issueLines->get($line['issue_item_id']);
                abort_if(! $issueLine, 422, 'That line does not belong to this challan.');

                $qty = (float) $line['returned_qty'];
                $itemName = $issueLine->item?->item_name ?? "item #{$issueLine->item_id}";
                $returnable = (float) $issueLine->issued_qty - ($returned[$issueLine->id] ?? 0);

                if ($qty > $returnable + 0.0001) {
                    throw ValidationException::withMessages([
                        'items' => "\"{$itemName}\" — only " . inv_qty(max($returnable, 0)) . ' is left to return on this challan (issued ' . inv_qty($issueLine->issued_qty) . ').',
                    ]);
                }

                $rate = (float) $issueLine->unit_rate;
                $common = [
                    'item_id'          => $issueLine->item_id,
                    'color_id'         => $issueLine->color_id,
                    'size_id'          => $issueLine->size_id,
                    'transaction_date' => $data['return_date'],
                    'transaction_type' => 'issue_return',
                    'rate'             => $rate,
                    'department_id'    => $issue->department_id,
                    'reference_type'   => 'inv_issue_return',
                    'reference_id'     => $return->id,
                    'remarks'          => "Return {$return->return_no} of issue {$issue->issue_no}",
                    'created_by'       => auth()->id(),
                ];

                // The issue moved the goods into a floor store: take them back out of it first.
                if ($issue->to_store_id) {
                    $available = $this->stock->currentStock($issueLine->item_id, $issue->to_store_id, $issueLine->color_id, $issueLine->size_id);
                    if ($qty > $available + 0.0001) {
                        throw ValidationException::withMessages([
                            'items' => "\"{$itemName}\" — only " . inv_qty(max($available, 0)) . " is still in {$issue->toStore?->name}; the rest has already been used there.",
                        ]);
                    }
                    $this->stock->post($common + ['store_id' => $issue->to_store_id, 'qty_out' => $qty]);
                }

                $this->stock->post($common + ['store_id' => $issue->store_id, 'qty_in' => $qty]);

                $return->items()->create([
                    'issue_item_id' => $issueLine->id,
                    'item_id'       => $issueLine->item_id,
                    'color_id'      => $issueLine->color_id,
                    'size_id'       => $issueLine->size_id,
                    'returned_qty'  => $qty,
                    'unit_rate'     => $rate,
                    'amount'        => round($qty * $rate, 2),
                ]);
            }

            return $return;
        });

        return redirect()->route('inventory.issue-returns.index')->with('success', "Return {$return->return_no} saved — stock is back in {$issue->store?->name}.");
    }

    public function print(InvIssueReturn $issueReturn): View
    {
        $this->authorize('inv_issue.return');

        $issueReturn->load(['items.item.category', 'items.item.unit', 'items.color', 'items.size', 'issue.department', 'issue.buyer', 'issue.toStore', 'store', 'returner']);

        return view('sfl-inventory::admin.issue-returns.print', ['return' => $issueReturn]);
    }

    /** Already-returned qty per issue line of this challan: [issue_item_id => qty]. */
    private function returnedByLine(InvIssue $issue): array
    {
        return InvIssueReturnItem::query()
            ->whereIn('issue_item_id', $issue->items->pluck('id'))
            ->whereHas('issueReturn')
            ->groupBy('issue_item_id')
            ->selectRaw('issue_item_id, SUM(returned_qty) as qty')
            ->pluck('qty', 'issue_item_id')
            ->map(fn ($qty) => (float) $qty)
            ->all();
    }
}
