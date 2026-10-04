<?php

namespace ME\SflInventory\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvRequisitionItem;
use ME\SflInventory\Models\InvStockTransaction;

/**
 * Single source of truth for all stock quantities and values. Nothing in this
 * package ever stores a "current stock" column — every figure here is derived
 * live from the inv_stock_transactions ledger, per the inventory-engine rule
 * in src/work/prompt.md: "Do NOT store current stock manually."
 *
 * Every quantity/value method below takes optional trailing $colorId/$sizeId
 * params. Left null (the default), a figure aggregates across every variant
 * of the item — today's exact behavior, so every pre-existing caller keeps
 * compiling and working unchanged. Passed explicitly, a figure is scoped to
 * that one item+color+size combination — used once an item is tracked as a
 * "generic" multi-variant item (its own color_id/size_id on inv_items is
 * null) rather than a single fixed variant.
 */
class StockService
{
    /**
     * Post one ledger row. A row must be either an inflow (qty_in > 0) or an
     * outflow (qty_out > 0), never both. For outflows where no rate is given,
     * the rate defaults to the item's current moving-average cost for that
     * exact variant in that store just before this transaction, so the
     * ledger always reconciles.
     */
    public function post(array $data): InvStockTransaction
    {
        $qtyIn = (float) ($data['qty_in'] ?? 0);
        $qtyOut = (float) ($data['qty_out'] ?? 0);
        $colorId = isset($data['color_id']) ? (int) $data['color_id'] : null;
        $sizeId = isset($data['size_id']) ? (int) $data['size_id'] : null;

        $rate = $data['rate'] ?? null;
        if ($rate === null) {
            $rate = $qtyOut > 0 ? $this->averageRate((int) $data['item_id'], (int) $data['store_id'], $colorId, $sizeId) : 0;
        }

        $value = round(($qtyIn > 0 ? $qtyIn : $qtyOut) * $rate, 2);

        return InvStockTransaction::create([
            'item_id'          => $data['item_id'],
            'color_id'         => $colorId,
            'size_id'          => $sizeId,
            'store_id'         => $data['store_id'],
            'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
            'transaction_type' => $data['transaction_type'],
            'qty_in'           => $qtyIn,
            'qty_out'          => $qtyOut,
            'rate'             => $rate,
            'value'            => $value,
            'reference_type'   => $data['reference_type'] ?? null,
            'reference_id'     => $data['reference_id'] ?? null,
            'department_id'    => $data['department_id'] ?? null,
            'remarks'          => $data['remarks'] ?? null,
            'created_by'       => $data['created_by'] ?? auth()->id(),
        ]);
    }

    public function currentStock(int $itemId, ?int $storeId = null, ?int $colorId = null, ?int $sizeId = null): float
    {
        $query = InvStockTransaction::where('item_id', $itemId);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        $this->applyVariant($query, $colorId, $sizeId);

        return (float) $query->selectRaw('COALESCE(SUM(qty_in), 0) - COALESCE(SUM(qty_out), 0) as balance')->value('balance');
    }

    /**
     * Report-facing valuation: current stock quantity priced at the item's
     * latest known rate (not a weighted average) — per request, every report
     * showing "Stock Value" should reflect the latest rate, not the moving
     * average. Internal COGS costing (Issue/Transfer/Production Consumption/
     * Adjustment postings) is unaffected — that still uses averageRate()
     * below, which stays a true ledger-derived weighted average.
     */
    public function stockValue(int $itemId, ?int $storeId = null, ?int $colorId = null, ?int $sizeId = null): float
    {
        return round($this->currentStock($itemId, $storeId, $colorId, $sizeId) * $this->latestRate($itemId, $storeId, $colorId, $sizeId), 2);
    }

    /**
     * Rate of the item's most recent purchase (GRN, or the opening-stock
     * entry if it's never been purchased since) — the "last known price".
     * Deliberately restricted to these two source-of-truth types: any other
     * transaction type (issue, transfer, adjustment, etc.) carries a
     * *derived* moving-average cost, not a real price, so picking up
     * whichever happened to post last would silently reintroduce averaging
     * through the back door instead of showing the actual latest rate.
     */
    public function latestRate(int $itemId, ?int $storeId = null, ?int $colorId = null, ?int $sizeId = null): float
    {
        // store_change inflows (from the older transfer-style item store
        // change) carried the purchase price over, so they count as a
        // price source for their store too.
        $query = InvStockTransaction::where('item_id', $itemId)
            ->where(fn ($q) => $q->whereIn('transaction_type', ['grn', 'opening'])
                ->orWhere(fn ($q2) => $q2->where('transaction_type', 'store_change')->where('qty_in', '>', 0)))
            ->where('rate', '>', 0);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        $this->applyVariant($query, $colorId, $sizeId);

        return (float) ($query->orderByDesc('transaction_date')->orderByDesc('id')->value('rate') ?? 0);
    }

    /**
     * Moving weighted-average cost: ledger-derived stock value divided by
     * current stock quantity in a given store. Used only to cost outflow
     * transactions in post() — kept independent of stockValue() above so
     * changing how reports display "Stock Value" never changes what COGS
     * an Issue/Transfer/etc. actually gets posted at.
     */
    public function averageRate(int $itemId, int $storeId, ?int $colorId = null, ?int $sizeId = null): float
    {
        $qty = $this->currentStock($itemId, $storeId, $colorId, $sizeId);
        if ($qty <= 0) {
            return 0.0;
        }

        return round($this->ledgerDerivedValue($itemId, $storeId, $colorId, $sizeId) / $qty, 2);
    }

    private function ledgerDerivedValue(int $itemId, ?int $storeId = null, ?int $colorId = null, ?int $sizeId = null): float
    {
        $query = InvStockTransaction::where('item_id', $itemId);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        $this->applyVariant($query, $colorId, $sizeId);

        return (float) $query
            ->selectRaw('COALESCE(SUM(CASE WHEN qty_in > 0 THEN value ELSE -value END), 0) as stock_value')
            ->value('stock_value');
    }

    /**
     * Quantity committed to approved-but-not-yet-fully-issued requisitions
     * against a store, per item(+variant).
     */
    public function reservedStock(int $itemId, int $storeId, ?int $colorId = null, ?int $sizeId = null, ?int $excludeRequisitionId = null): float
    {
        return (float) InvRequisitionItem::query()
            ->join('inv_requisitions', 'inv_requisitions.id', '=', 'inv_requisition_items.requisition_id')
            ->whereNull('inv_requisitions.deleted_at')
            ->when($excludeRequisitionId, fn ($q) => $q->where('inv_requisitions.id', '!=', $excludeRequisitionId))
            ->where('inv_requisitions.store_id', $storeId)
            ->where('inv_requisition_items.item_id', $itemId)
            ->when($colorId !== null, fn ($q) => $q->where('inv_requisition_items.color_id', $colorId))
            ->when($sizeId !== null, fn ($q) => $q->where('inv_requisition_items.size_id', $sizeId))
            ->whereIn('inv_requisitions.status', ['approved', 'partially_issued'])
            ->selectRaw('COALESCE(SUM(inv_requisition_items.approved_qty - inv_requisition_items.issued_qty), 0) as reserved')
            ->value('reserved');
    }

    public function availableStock(int $itemId, int $storeId, ?int $colorId = null, ?int $sizeId = null, ?int $excludeRequisitionId = null): float
    {
        return $this->currentStock($itemId, $storeId, $colorId, $sizeId) - $this->reservedStock($itemId, $storeId, $colorId, $sizeId, $excludeRequisitionId);
    }

    /**
     * Moves an item to a new store by rewriting its history: every ledger
     * row it has in another General/Buyer store is re-pointed at $storeId,
     * so every report — old periods included — shows the item as if it had
     * always lived there. No new entries are posted, so quantity and value
     * are exactly what they were, just in one store. Production-floor and
     * Finish Store rows are left alone (that stock genuinely left the main
     * store). Any 'store_change' pairs from the older transfer-style move
     * now sit in one store and cancel out, so they're removed. This is the
     * one deliberate exception to the ledger's insert-only rule, made on
     * purpose when the item's store is corrected. Returns rows moved.
     */
    public function moveItemHistoryToStore(InvItem $item, int $storeId): int
    {
        $sourceStoreIds = \ME\SflInventory\Models\InvStore::whereIn('type', [
            \ME\SflInventory\Models\InvStore::TYPE_GENERAL,
            \ME\SflInventory\Models\InvStore::TYPE_BUYER,
        ])->pluck('id')->push($storeId)->unique();

        $moved = DB::table('inv_stock_transactions')
            ->where('item_id', $item->id)
            ->where('store_id', '!=', $storeId)
            ->whereIn('store_id', $sourceStoreIds)
            ->update(['store_id' => $storeId, 'updated_at' => now()]);

        DB::table('inv_stock_transactions')
            ->where('item_id', $item->id)
            ->where('transaction_type', 'store_change')
            ->delete();

        return $moved;
    }

    /**
     * Buyer Store stock is owned per buyer + style: an item received under
     * one style can't be issued against another. Returns what was received
     * (posted GRNs) and issued (every live challan — a challan claims its
     * qty the moment it's prepared) for this item(+variant) in this store
     * under the given style, and the balance left for it.
     *
     * A style is identified by its Merchandising style id when there is one;
     * older, unlinked documents by buyer + style text.
     *
     * @return array{received: float, issued: float, balance: float}
     */
    public function styleBalance(int $itemId, int $storeId, ?int $buyerId, ?string $style, ?int $merStyleId, ?int $colorId = null, ?int $sizeId = null, ?int $excludeIssueId = null): array
    {
        $style = trim((string) $style);
        $sameStyle = function ($q, string $t) use ($buyerId, $style, $merStyleId) {
            $q->where(function ($w) use ($t, $buyerId, $style, $merStyleId) {
                if ($merStyleId) {
                    $w->where("{$t}.msfl_style_id", $merStyleId);
                }
                $w->orWhere(function ($old) use ($t, $buyerId, $style, $merStyleId) {
                    if ($merStyleId) {
                        $old->whereNull("{$t}.msfl_style_id");
                    }
                    $buyerId === null ? $old->whereNull("{$t}.buyer_id") : $old->where("{$t}.buyer_id", $buyerId);
                    $old->whereRaw("TRIM(COALESCE({$t}.style, '')) = ?", [$style]);
                });
            });
        };
        $variant = function ($q, string $t) use ($colorId, $sizeId) {
            $q->when($colorId !== null, fn ($w) => $w->where("{$t}.color_id", $colorId))
                ->when($sizeId !== null, fn ($w) => $w->where("{$t}.size_id", $sizeId));
        };

        $received = (float) DB::table('inv_grn_items as gi')
            ->join('inv_grns as g', 'g.id', '=', 'gi.grn_id')
            ->whereNull('g.deleted_at')
            ->where('g.status', 'posted')
            ->where('g.store_id', $storeId)
            ->where('gi.item_id', $itemId)
            ->tap(fn ($q) => $variant($q, 'gi'))
            ->tap(fn ($q) => $sameStyle($q, 'g'))
            ->sum('gi.received_qty');

        $issued = (float) DB::table('inv_issue_items as ii')
            ->join('inv_issues as i', 'i.id', '=', 'ii.issue_id')
            ->whereNull('i.deleted_at')
            ->where('i.store_id', $storeId)
            ->where('ii.item_id', $itemId)
            ->when($excludeIssueId, fn ($q) => $q->where('i.id', '!=', $excludeIssueId))
            ->tap(fn ($q) => $variant($q, 'ii'))
            ->tap(fn ($q) => $sameStyle($q, 'i'))
            ->sum('ii.issued_qty');

        return ['received' => $received, 'issued' => $issued, 'balance' => $received - $issued];
    }

    /** @return array{current: float, reserved: float, available: float} */
    public function stockSnapshot(int $itemId, int $storeId, ?int $colorId = null, ?int $sizeId = null, ?int $excludeRequisitionId = null): array
    {
        $current = $this->currentStock($itemId, $storeId, $colorId, $sizeId);
        $reserved = $this->reservedStock($itemId, $storeId, $colorId, $sizeId, $excludeRequisitionId);

        return ['current' => $current, 'reserved' => $reserved, 'available' => $current - $reserved];
    }

    /**
     * Low stock is evaluated on total stock across all stores (and every
     * variant) against the item-level minimum_stock threshold, matching the
     * Item Master design — there is no per-variant minimum stock concept.
     */
    public function isLowStock(InvItem $item): bool
    {
        if ((float) $item->minimum_stock <= 0) {
            return false;
        }

        return $this->currentStock($item->id) <= (float) $item->minimum_stock;
    }

    /**
     * Dead stock: still holds stock, but nothing has moved it out in
     * config('sfl-inventory.dead_stock_days') days (default 90). Evaluated
     * item-wide (every variant together), same reasoning as isLowStock().
     */
    public function isDeadStock(InvItem $item, ?int $storeId = null): bool
    {
        if ($this->currentStock($item->id, $storeId) <= 0) {
            return false;
        }

        $query = InvStockTransaction::where('item_id', $item->id)->where('qty_out', '>', 0);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $lastOutbound = $query->max('transaction_date');
        $days = (int) config('sfl-inventory.dead_stock_days', 90);

        return $lastOutbound === null || Carbon::parse($lastOutbound)->lt(now()->subDays($days));
    }

    private function applyVariant($query, ?int $colorId, ?int $sizeId): void
    {
        if ($colorId !== null) {
            $query->where('color_id', $colorId);
        }
        if ($sizeId !== null) {
            $query->where('size_id', $sizeId);
        }
    }
}
