<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvPurchaseOrder;
use ME\SflInventory\Services\DocumentNumberService;

class InvPurchaseOrderObserver
{
    public function __construct(private readonly DocumentNumberService $documentNumbers)
    {
    }

    public function creating(InvPurchaseOrder $po): void
    {
        if (empty($po->po_number)) {
            $po->po_number = $this->documentNumbers->next(
                InvPurchaseOrder::class,
                'po_number',
                config('sfl-inventory.document_prefixes.purchase_order', 'PO')
            );
        }
    }
}
