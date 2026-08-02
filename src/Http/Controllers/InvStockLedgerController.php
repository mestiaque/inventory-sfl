<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvStockTransaction;
use ME\SflInventory\Models\InvStore;

class InvStockLedgerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_stock_ledger.view');

        $transactions = InvStockTransaction::query()
            ->with(['item', 'store', 'department'])
            ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->item_id))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('transaction_type'), fn ($q) => $q->where('transaction_type', $request->transaction_type))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('transaction_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('transaction_date', '<=', $request->date_to))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $items = InvItem::orderBy('item_name')->get();
        $stores = InvStore::orderBy('name')->get();
        $types = ['opening', 'grn', 'issue', 'transfer', 'production_consumption', 'finished_goods', 'gate_pass', 'shipment', 'adjustment'];

        return view('sfl-inventory::admin.stock-ledger.index', compact('transactions', 'items', 'stores', 'types'));
    }
}
