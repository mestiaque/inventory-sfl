<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvItemCategory;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\StockService;

class InvStockOverviewController extends Controller
{
    public function __construct(private readonly StockService $stock)
    {
    }

    /**
     * Main Store Inventory: current / reserved / available / value per
     * item x store — computed live from the ledger via StockService, never
     * stored. Only iterates item/store combinations that actually have
     * ledger activity, not the full items x stores cross-product.
     */
    public function index(Request $request): View
    {
        $this->authorize('inv_stock_overview.view');

        $itemIdsInCategory = $request->filled('category_id')
            ? InvItem::where('category_id', $request->category_id)->pluck('id')
            : null;

        $combos = DB::table('inv_stock_transactions')
            ->select('item_id', 'store_id')
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->item_id))
            ->when($itemIdsInCategory !== null, fn ($q) => $q->whereIn('item_id', $itemIdsInCategory))
            ->groupBy('item_id', 'store_id')
            ->get();

        $rows = $combos->map(function ($combo) {
            $current = $this->stock->currentStock($combo->item_id, $combo->store_id);
            $reserved = $this->stock->reservedStock($combo->item_id, $combo->store_id);

            return (object) [
                'item_id'   => $combo->item_id,
                'store_id'  => $combo->store_id,
                'current'   => $current,
                'reserved'  => $reserved,
                'available' => $current - $reserved,
                'value'     => $this->stock->stockValue($combo->item_id, $combo->store_id),
            ];
        })->filter(fn ($row) => $row->current != 0 || $row->reserved != 0)
            ->sortByDesc('value')
            ->values();

        $items = InvItem::whereIn('id', $rows->pluck('item_id')->unique())->with('category', 'unit')->get()->keyBy('id');
        $stores = InvStore::whereIn('id', $rows->pluck('store_id')->unique())->get()->keyBy('id');

        $categories = InvItemCategory::active()->orderBy('name')->get();
        $allStores = InvStore::active()->orderBy('name')->get();
        $allItems = InvItem::active()->orderBy('item_name')->get();

        return view('sfl-inventory::admin.stock-overview.index', compact('rows', 'items', 'stores', 'categories', 'allStores', 'allItems'));
    }
}
