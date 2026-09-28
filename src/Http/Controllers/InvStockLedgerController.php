<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Models\InvColor;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvSize;
use ME\SflInventory\Models\InvStockTransaction;
use ME\SflInventory\Models\InvStore;

class InvStockLedgerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_stock_ledger.view');

        $transactionsQuery = InvStockTransaction::query()
            ->with(['item', 'color', 'size', 'store', 'department'])
            ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->item_id))
            ->when($request->filled('color_id'), fn ($q) => $q->where('color_id', $request->color_id))
            ->when($request->filled('size_id'), fn ($q) => $q->where('size_id', $request->size_id))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('transaction_type'), fn ($q) => $q->where('transaction_type', $request->transaction_type))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('transaction_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('transaction_date', '<=', $request->date_to));

        // Reversal rows are hidden from the list, but the totals net them
        // off (a GRN reversal cancels an inflow, an issue reversal an
        // outflow) so In − Out always equals the real balance.
        $totals = (clone $transactionsQuery)->selectRaw("
            COALESCE(SUM(CASE WHEN transaction_type LIKE '%\\_reversal' THEN -qty_out ELSE qty_in END), 0) as qty_in,
            COALESCE(SUM(CASE WHEN transaction_type LIKE '%\\_reversal' THEN -qty_in ELSE qty_out END), 0) as qty_out,
            COALESCE(SUM(CASE WHEN qty_in > 0 THEN value ELSE -value END), 0) as value
        ")->reorder()->first();

        $transactions = $transactionsQuery
            ->where('transaction_type', 'not like', '%\_reversal')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $items = InvItem::orderBy('item_name')->get();
        $colors = InvColor::active()->orderBy('name')->get();
        $sizes = InvSize::active()->ordered()->get();
        $stores = InvStore::orderBy('name')->get();
        $types = ['opening', 'grn', 'issue', 'transfer', 'production_consumption', 'finished_goods', 'gate_pass', 'shipment', 'adjustment'];

        return view('sfl-inventory::admin.stock-ledger.index', compact('transactions', 'items', 'colors', 'sizes', 'stores', 'types', 'totals'));
    }
}
