<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvPurchaseRequisition;
use ME\SflInventory\Services\DocumentNumberService;

class InvPurchaseRequisitionObserver
{
    public function __construct(private readonly DocumentNumberService $documentNumbers)
    {
    }

    public function creating(InvPurchaseRequisition $purchaseRequisition): void
    {
        if (empty($purchaseRequisition->requisition_no)) {
            $purchaseRequisition->requisition_no = $this->documentNumbers->next(
                InvPurchaseRequisition::class,
                'requisition_no',
                config('sfl-inventory.document_prefixes.purchase_requisition', 'PREQ')
            );
        }
    }
}
