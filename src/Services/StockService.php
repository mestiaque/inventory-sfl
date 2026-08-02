<?php

namespace ME\SflInventory\Services;

use Carbon\Carbon;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvRequisitionItem;
use ME\SflInventory\Models\InvStockTransaction;

/**
 * Single source of truth for all stock quantities and values. Nothing in this
 * package ever stores a "current stock" column — every figure here is derived
 * live from the inv_stock_transactions ledger, per the inventory-engine rule
 * in src/work/prompt.md: "Do NOT store current stock manually."
 */
class StockService
{
    /**
     * Post one ledger row. A row must be either an inflow (qty_in > 0) or an
     * outflow (qty_out > 0), never both. For outflows where no rate is given,
     * the rate defaults to the item's current moving-average cost in that
     * store just before this transaction, so the ledger always reconciles.
     */
    public function post(array $data): InvStockTransaction
    {
        $qtyIn = (float) ($data['qty_in'] ?? 0);
        $qtyOut = (float) ($data['qty_out'] ?? 0);

        $rate = $data['rate'] ?? null;
        if ($rate === null) {
            $rate = $qtyOut > 0 ? $this->averageRate((int) $data['item_id'], (int) $data['store_id']) : 0;
        }

        $value = round(($qtyIn > 0 ? $qtyIn : $qtyOut) * $rate, 2);

        return InvStockTransaction::create([
            'item_id'          => $data['item_id'],
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

    public function currentStock(int $itemId, ?int $storeId = null): float
    {
        $query = InvStockTransaction::where('item_id', $itemId);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return (float) $query->selectRaw('COALESCE(SUM(qty_in), 0) - COALESCE(SUM(qty_out), 0) as balance')->value('balance');
    }

    public function stockValue(int $itemId, ?int $storeId = null): float
    {
        $query = InvStockTransaction::where('item_id', $itemId);
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return (float) $query
            ->selectRaw('COALESCE(SUM(CASE WHEN qty_in > 0 THEN value ELSE -value END), 0) as stock_value')
            ->value('stock_value');
    }

    /**
     * Moving weighted-average cost: current stock value divided by current
     * stock quantity in a given store.
     */
    public function averageRate(int $itemId, int $storeId): float
    {
        $qty = $this->currentStock($itemId, $storeId);
        if ($qty <= 0) {
            return 0.0;
        }

        return round($this->stockValue($itemId, $storeId) / $qty, 2);
    }

    /**
     * Quantity committed to approved-but-not-yet-fully-issued requisitions
     * against a store, per item.
     */
    public function reservedStock(int $itemId, int $storeId): float
    {
        return (float) InvRequisitionItem::query()
            ->join('inv_requisitions', 'inv_requisitions.id', '=', 'inv_requisition_items.requisition_id')
            ->where('inv_requisitions.store_id', $storeId)
            ->where('inv_requisition_items.item_id', $itemId)
            ->whereIn('inv_requisitions.status', ['approved', 'partially_issued'])
            ->selectRaw('COALESCE(SUM(inv_requisition_items.approved_qty - inv_requisition_items.issued_qty), 0) as reserved')
            ->value('reserved');
    }

    public function availableStock(int $itemId, int $storeId): float
    {
        return $this->currentStock($itemId, $storeId) - $this->reservedStock($itemId, $storeId);
    }

    /**
     * Low stock is evaluated on total stock across all stores against the
     * item-level minimum_stock threshold, matching the Item Master design.
     */
    public function isLowStock(InvItem $item): bool
    {
        if ((float) $item->minimum_stock <= 0) {
            return false;
        }

        return $this->currentStock($item->id) < (float) $item->minimum_stock;
    }

    /**
     * Dead stock: still holds stock, but nothing has moved it out in
     * config('sfl-inventory.dead_stock_days') days (default 90).
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
}
