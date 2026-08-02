<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvRequisition;
use ME\SflInventory\Services\DocumentNumberService;

class InvRequisitionObserver
{
    public function __construct(private readonly DocumentNumberService $documentNumbers)
    {
    }

    public function creating(InvRequisition $requisition): void
    {
        if (empty($requisition->requisition_no)) {
            $requisition->requisition_no = $this->documentNumbers->next(
                InvRequisition::class,
                'requisition_no',
                config('sfl-inventory.document_prefixes.requisition', 'REQ')
            );
        }
    }
}
