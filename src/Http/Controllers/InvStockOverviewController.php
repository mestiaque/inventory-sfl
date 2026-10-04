<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Models\InvColor;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvItemCategory;
use ME\SflInventory\Models\InvRequisition;
use ME\SflInventory\Models\InvSize;
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
            ->select('item_id', 'color_id', 'size_id', 'store_id')
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->item_id))
            ->when($request->filled('color_id'), fn ($q) => $q->where('color_id', $request->color_id))
            ->when($request->filled('size_id'), fn ($q) => $q->where('size_id', $request->size_id))
            ->when($itemIdsInCategory !== null, fn ($q) => $q->whereIn('item_id', $itemIdsInCategory))
            ->groupBy('item_id', 'color_id', 'size_id', 'store_id')
            ->get();

        $rows = $combos->map(function ($combo) {
            $current = $this->stock->currentStock($combo->item_id, $combo->store_id, $combo->color_id, $combo->size_id);
            $reserved = $this->stock->reservedStock($combo->item_id, $combo->store_id, $combo->color_id, $combo->size_id);

            return (object) [
                'item_id'   => $combo->item_id,
                'color_id'  => $combo->color_id,
                'size_id'   => $combo->size_id,
                'store_id'  => $combo->store_id,
                'current'   => $current,
                'reserved'  => $reserved,
                'available' => $current - $reserved,
                'value'     => $this->stock->stockValue($combo->item_id, $combo->store_id, $combo->color_id, $combo->size_id),
            ];
        })->filter(fn ($row) => $row->current != 0 || $row->reserved != 0)
            ->sortByDesc('value')
            ->values();

        $items = InvItem::whereIn('id', $rows->pluck('item_id')->unique())->with('category', 'unit')->get()->keyBy('id');
        $stores = InvStore::whereIn('id', $rows->pluck('store_id')->unique())->get()->keyBy('id');
        $colors = InvColor::whereIn('id', $rows->pluck('color_id')->filter()->unique())->get()->keyBy('id');
        $sizes = InvSize::whereIn('id', $rows->pluck('size_id')->filter()->unique())->get()->keyBy('id');

        $categories = InvItemCategory::active()->orderBy('name')->get();
        $allStores = InvStore::active()->orderBy('name')->get();
        $allItems = InvItem::active()->orderBy('item_name')->get();
        $allColors = InvColor::active()->orderBy('name')->get();
        $allSizes = InvSize::active()->ordered()->get();

        return view('sfl-inventory::admin.stock-overview.index', compact('rows', 'items', 'stores', 'colors', 'sizes', 'categories', 'allStores', 'allItems', 'allColors', 'allSizes'));
    }

    /**
     * Live Current / Reserved / Available for one item(+variant) in one store
     * — feeds the stock column on the Requisition, Requisition Approval and
     * Issue forms. $exclude_requisition_id leaves that requisition's own
     * reservation out, so approving it doesn't count against itself.
     */
    public function balance(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->canAny(['inv_requisition.add', 'inv_requisition.edit', 'inv_requisition.approve', 'inv_issue.add', 'inv_stock_overview.view']), 403);

        $data = $request->validate([
            'store_id' => ['required', 'integer'],
            'item_id'  => ['required', 'integer'],
            'color_id' => ['nullable', 'integer'],
            'size_id'  => ['nullable', 'integer'],
            'exclude_requisition_id' => ['nullable', 'integer'],
            'requisition_id' => ['nullable', 'integer'],
        ]);

        $colorId = isset($data['color_id']) ? (int) $data['color_id'] : null;
        $sizeId = isset($data['size_id']) ? (int) $data['size_id'] : null;

        $snapshot = $this->stock->stockSnapshot(
            (int) $data['item_id'],
            (int) $data['store_id'],
            $colorId,
            $sizeId,
            isset($data['exclude_requisition_id']) ? (int) $data['exclude_requisition_id'] : null,
        );

        // Buyer Store: also the balance left under the requisition's buyer + style.
        $requisition = isset($data['requisition_id']) ? InvRequisition::find($data['requisition_id']) : null;
        if ($requisition && InvStore::whereKey($data['store_id'])->value('type') === InvStore::TYPE_BUYER) {
            $snapshot['style'] = $this->stock->styleBalance(
                (int) $data['item_id'], (int) $data['store_id'], $requisition->buyer_id, $requisition->style, $requisition->msfl_style_id, $colorId, $sizeId
            ) + ['label' => $requisition->style ?: '(no style)'];
        }

        return response()->json($snapshot);
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
            : null;

        $categories = InvItemCategory::active()->orderBy('name')->get();

        $itemsQuery = InvItem::query()
            ->with('category', 'openingStore')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('item_code', 'like', '%' . $request->search . '%')
                ->orWhere('item_name', 'like', '%' . $request->search . '%')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($store, fn ($q) => $q->where('opening_store_id', $store->id));

        $balances = DB::table('inv_stock_transactions')
            ->when($store, fn ($q) => $q->where('store_id', $store->id))
            ->select('item_id', DB::raw('SUM(qty_in) - SUM(qty_out) as bal'))
            ->groupBy('item_id')->pluck('bal', 'item_id');

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
