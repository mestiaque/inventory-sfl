<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvStockAdjustment;
use ME\SflInventory\Services\DocumentNumberService;

class InvStockAdjustmentObserver
{
    public function __construct(private readonly DocumentNumberService $documentNumbers)
    {
    }

    public function creating(InvStockAdjustment $adjustment): void
    {
        if (empty($adjustment->adjustment_no)) {
            $adjustment->adjustment_no = $this->documentNumbers->next(
                InvStockAdjustment::class,
                'adjustment_no',
                config('sfl-inventory.document_prefixes.adjustment', 'ADJ')
            );
        }
    }
}
