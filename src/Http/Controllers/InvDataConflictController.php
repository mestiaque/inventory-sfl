<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\StockService;

/**
 * Data Conflicts: history that breaks the "one item, one store" and "Buyer
 * Store stock belongs to a buyer + style" rules — mostly left over from
 * before those rules were enforced on new documents. Read-only except for
 * "Move into own store", which consolidates an item's stray balances the
 * same way changing its store in Item Master does.
 */
class InvDataConflictController extends Controller
{
    public function __construct(private readonly StockService $stock)
    {
    }

    public function index(): View
    {
        $this->authorize('inv_negative_stock.view');

        // 1. Stock sitting in a store other than the item's own.
        $strayStock = DB::table('inv_stock_transactions as t')
            ->join('inv_items as i', fn ($j) => $j->on('i.id', '=', 't.item_id')->whereNull('i.deleted_at'))
            ->join('inv_stores as s', 's.id', '=', 't.store_id')
            ->join('inv_stores as own', 'own.id', '=', 'i.opening_store_id')
            ->whereColumn('t.store_id', '!=', 'i.opening_store_id')
            ->groupBy('t.item_id', 't.store_id', 'i.item_code', 'i.item_name', 's.name', 'own.name')
            ->havingRaw('ABS(SUM(t.qty_in) - SUM(t.qty_out)) > 0.0001')
            ->selectRaw('t.item_id, i.item_code, i.item_name, s.name as store_name, own.name as own_store, SUM(t.qty_in) - SUM(t.qty_out) as balance')
            ->orderBy('i.item_code')
            ->get();

        // 2. Items with stock but no store — hidden from every document form until given one.
        $noStore = DB::table('inv_stock_transactions as t')
            ->join('inv_items as i', fn ($j) => $j->on('i.id', '=', 't.item_id')->whereNull('i.deleted_at')->whereNull('i.opening_store_id'))
            ->join('inv_stores as s', 's.id', '=', 't.store_id')
            ->groupBy('t.item_id', 'i.item_code', 'i.item_name')
            ->selectRaw("t.item_id, i.item_code, i.item_name, SUM(t.qty_in) - SUM(t.qty_out) as balance, GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ') as stores")
            ->orderBy('i.item_code')
            ->get();

        // 3. Documents posted against a store that isn't the item's own.
        $wrongStoreDocs = collect([
            ['Issue', 'inv_issues', 'inv_issue_items', 'issue_id', 'issue_no', 'issue_date', 'issued_qty', "x.status = 'approved'"],
            ['GRN', 'inv_grns', 'inv_grn_items', 'grn_id', 'grn_number', 'receive_date', 'received_qty', "x.status = 'posted'"],
            ['Requisition', 'inv_requisitions', 'inv_requisition_items', 'requisition_id', 'requisition_no', 'requisition_date', 'requested_qty', '1 = 1'],
        ])->flatMap(fn ($d) => DB::table("{$d[1]} as x")
            ->join("{$d[2]} as li", "li.{$d[3]}", '=', 'x.id')
            ->join('inv_items as i', 'i.id', '=', 'li.item_id')
            ->join('inv_stores as s', 's.id', '=', 'x.store_id')
            ->join('inv_stores as own', 'own.id', '=', 'i.opening_store_id')
            ->whereNull('x.deleted_at')
            ->whereRaw($d[7])
            ->whereColumn('x.store_id', '!=', 'i.opening_store_id')
            ->selectRaw("? as doc_type, x.{$d[4]} as doc_no, x.{$d[5]} as doc_date, i.item_code, i.item_name, li.{$d[6]} as qty, s.name as store_name, own.name as own_store", [$d[0]])
            ->get())
            ->sortByDesc('doc_date')
            ->values();

        // 4. Buyer Store: issued more under a buyer + style than was received under it.
        $buyerStoreIds = InvStore::where('type', InvStore::TYPE_BUYER)->pluck('id');
        $styleOverIssues = DB::query()->fromSub(
            DB::table('inv_issues as iss')
                ->join('inv_issue_items as ii', 'ii.issue_id', '=', 'iss.id')
                ->whereNull('iss.deleted_at')
                ->whereIn('iss.store_id', $buyerStoreIds)
                ->groupBy('iss.store_id', 'iss.buyer_id', DB::raw("TRIM(COALESCE(iss.style, ''))"), 'ii.item_id')
                ->selectRaw("iss.store_id, iss.buyer_id, TRIM(COALESCE(iss.style, '')) as style, ii.item_id, SUM(ii.issued_qty) as issued, GROUP_CONCAT(DISTINCT iss.issue_no ORDER BY iss.issue_no SEPARATOR ', ') as issue_nos"),
            'z'
        )
            ->leftJoin('inv_items as i', 'i.id', '=', 'z.item_id')
            ->leftJoin('inv_buyers as b', 'b.id', '=', 'z.buyer_id')
            ->selectRaw("z.*, i.item_code, i.item_name, b.name as buyer_name,
                (SELECT COALESCE(SUM(gi.received_qty), 0) FROM inv_grn_items gi JOIN inv_grns g ON g.id = gi.grn_id
                  WHERE g.deleted_at IS NULL AND g.status = 'posted' AND g.store_id = z.store_id AND gi.item_id = z.item_id
                    AND g.buyer_id <=> z.buyer_id AND TRIM(COALESCE(g.style, '')) = z.style) as received")
            ->get()
            ->filter(fn ($r) => (float) $r->issued > (float) $r->received + 0.0001)
            ->sortBy([['style', 'asc'], ['item_code', 'asc']])
            ->values();

        return view('sfl-inventory::admin.data-conflicts.index', compact('strayStock', 'noStore', 'wrongStoreDocs', 'styleOverIssues'));
    }

    /** Moves every stray balance of this item into its own store. */
    public function consolidate(InvItem $item): RedirectResponse
    {
        $this->authorize('inv_negative_stock.fix');

        if (! $item->opening_store_id) {
            return back()->with('error', "{$item->item_code} has no store assigned — set one in Item Master first.");
        }

        $moved = DB::transaction(fn () => $this->stock->consolidateItemStock($item, (int) $item->opening_store_id));

        return back()->with('success', "{$item->item_code}: {$moved} stray balance(s) moved into " . $item->openingStore?->name . '.');
    }
}
