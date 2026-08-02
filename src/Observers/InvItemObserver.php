<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Services\StockService;

class InvItemObserver
{
    public function __construct(
        private readonly StockService $stock,
    ) {
    }

    public function created(InvItem $item): void
    {
        if ($item->opening_stock > 0 && $item->opening_store_id) {
            $rate = round((float) $item->opening_value / (float) $item->opening_stock, 2);

            $this->stock->post([
                'item_id'          => $item->id,
                'store_id'         => $item->opening_store_id,
                'transaction_date' => $item->created_at?->toDateString() ?? now()->toDateString(),
                'transaction_type' => 'opening',
                'qty_in'           => $item->opening_stock,
                'rate'             => $rate,
                'reference_type'   => 'inv_item',
                'reference_id'     => $item->id,
                'remarks'          => 'Opening stock',
                'created_by'       => $item->created_by,
            ]);
        }
    }
}
