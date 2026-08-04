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

    /**
     * Card-grid view of every item's status/qty at one store — a visual
     * browse screen, distinct from index()'s ledger-activity-only table.
     * Unlike index(), this deliberately includes items with zero/no
     * transactions at the store (that's exactly what the yellow "active but
     * empty" dot is for), so it queries the full item master, not just
     * item/store combos that already have ledger rows.
     */
    public function cards(Request $request): View
    {
        $this->authorize('inv_stock_overview.view');

        $stores = InvStore::active()->orderBy('name')->get();
        $store = $request->filled('store_id')
            ? $stores->firstWhere('id', $request->integer('store_id'))
            : $stores->first();

        $categories = InvItemCategory::active()->orderBy('name')->get();

        $itemsQuery = InvItem::query()
            ->with('category')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('item_code', 'like', '%' . $request->search . '%')
                ->orWhere('item_name', 'like', '%' . $request->search . '%')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id));

        $balances = $store
            ? DB::table('inv_stock_transactions')->where('store_id', $store->id)
                ->select('item_id', DB::raw('SUM(qty_in) - SUM(qty_out) as bal'))
                ->groupBy('item_id')->pluck('bal', 'item_id')
            : collect();

        $allFilteredItems = $itemsQuery->orderBy('item_name')->get();

        $statusOf = function (InvItem $item) use ($balances) {
            if (! $item->is_active) {
                return 'inactive';
            }

            return (float) ($balances[$item->id] ?? 0) > 0 ? 'active' : 'empty';
        };

        $counts = ['active' => 0, 'empty' => 0, 'inactive' => 0];
        foreach ($allFilteredItems as $item) {
            $counts[$statusOf($item)]++;
        }

        // Always grouped by category — one 12-column grid per category
        // section, in a fixed order (each section's own item count, not paginated).
        $grouped = $allFilteredItems->groupBy(fn ($item) => $item->category?->name ?? 'Uncategorized');

        // Same Stocked/Empty/Inactive breakdown as the page header, but
        // scoped to each category's own items — shown on the section title row.
        $groupCounts = $grouped->map(function ($categoryItems) use ($statusOf) {
            $c = ['active' => 0, 'empty' => 0, 'inactive' => 0];
            foreach ($categoryItems as $item) {
                $c[$statusOf($item)]++;
            }
            return $c;
        });

        return view('sfl-inventory::admin.stock-overview.cards', [
            'stores' => $stores,
            'store' => $store,
            'categories' => $categories,
            'balances' => $balances,
            'statusOf' => $statusOf,
            'counts' => $counts,
            'groupCounts' => $groupCounts,
            'grouped' => $grouped,
        ]);
    }
}
