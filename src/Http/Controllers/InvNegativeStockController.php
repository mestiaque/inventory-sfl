<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Models\InvColor;
use ME\SflInventory\Models\InvIssue;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvSize;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\NegativeStockFixer;
use RuntimeException;

/**
 * Negative Stock Fix: finds every item+variant+store whose ledger balance
 * has gone below zero (e.g. an issue posted from the wrong store before the
 * per-store over-issue guard existed) and lets the user correct it — one at
 * a time, or all at once with Auto Fix. See NegativeStockFixer.
 */
class InvNegativeStockController extends Controller
{
    public function __construct(private readonly NegativeStockFixer $fixer)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_negative_stock.view');

        $rows = $this->fixer->negativeCombos($request->integer('item_id') ?: null, $request->integer('store_id') ?: null);

        $items = InvItem::whereIn('id', $rows->pluck('item_id')->unique())->with('category', 'unit')->get()->keyBy('id');
        $stores = InvStore::whereIn('id', $rows->pluck('store_id')->unique())->get()->keyBy('id');
        $colors = InvColor::withTrashed()->whereIn('id', $rows->pluck('color_id')->filter()->unique())->get()->keyBy('id');
        $sizes = InvSize::withTrashed()->whereIn('id', $rows->pluck('size_id')->filter()->unique())->get()->keyBy('id');

        $allItems = InvItem::active()->orderBy('item_name')->get();
        $allStores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.negative-stock.index', compact('rows', 'items', 'stores', 'colors', 'sizes', 'allItems', 'allStores'));
    }

    public function show(Request $request): View
    {
        $this->authorize('inv_negative_stock.view');

        [$itemId, $storeId, $colorId, $sizeId] = $this->comboFrom($request);

        $item = InvItem::with('unit', 'category')->findOrFail($itemId);
        $store = InvStore::findOrFail($storeId);
        $color = $colorId ? InvColor::withTrashed()->find($colorId) : null;
        $size = $sizeId ? InvSize::withTrashed()->find($sizeId) : null;

        $ledger = $this->fixer->comboQuery($itemId, $storeId, $colorId, $sizeId)
            ->orderBy('transaction_date')->orderBy('id')
            ->get();

        $running = 0;
        foreach ($ledger as $txn) {
            $running += (float) $txn->qty_in - (float) $txn->qty_out;
            $txn->running_balance = $running;
        }
        $balance = $running;

        // Same item+variant in every other store — where the stock most
        // likely should have been issued from.
        $otherStores = DB::table('inv_stock_transactions')
            ->where('item_id', $itemId)
            ->where('store_id', '!=', $storeId)
            ->tap(fn ($q) => $this->fixer->whereVariant($q, $colorId, $sizeId))
            ->select('store_id', DB::raw('SUM(qty_in) - SUM(qty_out) as balance'))
            ->groupBy('store_id')
            ->pluck('balance', 'store_id');
        $storeNames = InvStore::orderBy('name')->get()->keyBy('id');

        // Approved issues that took this item+variant out of this store —
        // candidates for "this was issued from the wrong store".
        $issueIds = $ledger->where('reference_type', 'inv_issue')->where('transaction_type', 'issue')
            ->where('qty_out', '>', 0)->pluck('reference_id')->unique();
        $issues = InvIssue::whereIn('id', $issueIds)
            ->where('store_id', $storeId)
            ->where('status', 'approved')
            ->with('items.item', 'items.color', 'items.size', 'department')
            ->orderByDesc('issue_date')->orderByDesc('id')
            ->get();

        $allStores = $this->fixer->sourceStores()->where('id', '!=', $storeId)->values();

        // Challans an earlier correction moved out of this store — can be
        // put back if that move turned out to be the wrong one.
        $movedAway = $this->fixer->movedAwayChallans($itemId, $colorId, $sizeId, $storeId);

        return view('sfl-inventory::admin.negative-stock.show', compact(
            'item', 'store', 'color', 'size', 'colorId', 'sizeId', 'ledger', 'balance',
            'otherStores', 'storeNames', 'issues', 'allStores', 'movedAway'
        ));
    }

    public function moveIssue(Request $request, InvIssue $issue): RedirectResponse
    {
        $this->authorize('inv_negative_stock.fix');

        $data = $request->validate([
            'target_store_id' => ['required', 'integer', 'exists:inv_stores,id'],
            'reason'          => ['nullable', 'string', 'max:500'],
        ]);

        $oldStore = $issue->store?->name;

        try {
            $this->fixer->moveIssue($issue, (int) $data['target_store_id'], $data['reason'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $newStore = InvStore::find($data['target_store_id'])?->name;

        return back()->with('success', "{$issue->issue_no} moved from {$oldStore} to {$newStore}. Stock corrected in both stores.");
    }

    public function undoMove(InvIssue $issue): RedirectResponse
    {
        $this->authorize('inv_negative_stock.fix');

        $from = $issue->store?->name;

        try {
            $this->fixer->undoMove($issue);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$issue->issue_no} moved back from {$from} to " . $issue->fresh('store')->store?->name . '.');
    }

    public function zeroOut(Request $request): RedirectResponse
    {
        $this->authorize('inv_negative_stock.fix');

        [$itemId, $storeId, $colorId, $sizeId] = $this->comboFrom($request);
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;
        $canApprove = auth()->user()->can('inv_adjustment.approve');

        $adjustment = $this->fixer->zeroOut($itemId, $storeId, $colorId, $sizeId, $reason, $canApprove);

        if (! $adjustment) {
            return back()->with('error', 'This stock is no longer negative — nothing to correct.');
        }

        return $canApprove
            ? redirect()->route('inventory.negative-stock.index')->with('success', "Adjustment {$adjustment->adjustment_no} posted — balance is now 0.")
            : redirect()->route('inventory.adjustments.index')->with('success', "Adjustment {$adjustment->adjustment_no} created and waiting for approval.");
    }

    /** Auto Fix preview — runs the whole fix in a rolled-back transaction. */
    public function autoFixPreview(): View
    {
        $this->authorize('inv_negative_stock.fix');

        $canApprove = auth()->user()->can('inv_adjustment.approve');
        $log = $this->fixer->autoFix($canApprove, dryRun: true);

        return view('sfl-inventory::admin.negative-stock.auto-fix', compact('log', 'canApprove'));
    }

    public function autoFixApply(): RedirectResponse
    {
        $this->authorize('inv_negative_stock.fix');

        $canApprove = auth()->user()->can('inv_adjustment.approve');
        $log = $this->fixer->autoFix($canApprove, dryRun: false);

        $moves = collect($log)->sum(fn ($e) => collect($e['actions'])->filter(fn ($a) => str_starts_with($a, 'Moved'))->count());
        $adjustments = collect($log)->sum(fn ($e) => collect($e['actions'])->reject(fn ($a) => str_starts_with($a, 'Moved'))->count());

        return redirect()->route('inventory.negative-stock.index')->with(
            'success',
            'Auto Fix done: ' . count($log) . " negative balance(s) processed — {$moves} challan(s) moved, {$adjustments} stock adjustment(s) created"
                . ($canApprove ? '.' : ' (waiting for approval in Stock Adjustment).')
        );
    }

    private function comboFrom(Request $request): array
    {
        $data = $request->validate([
            'item_id'  => ['required', 'integer'],
            'store_id' => ['required', 'integer'],
            'color_id' => ['nullable', 'integer'],
            'size_id'  => ['nullable', 'integer'],
        ]);

        return [
            (int) $data['item_id'],
            (int) $data['store_id'],
            isset($data['color_id']) ? (int) $data['color_id'] : null,
            isset($data['size_id']) ? (int) $data['size_id'] : null,
        ];
    }
}
