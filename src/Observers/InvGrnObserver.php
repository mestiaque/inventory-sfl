<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvGrn;
use ME\SflInventory\Services\DocumentNumberService;

class InvGrnObserver
{
    public function __construct(private readonly DocumentNumberService $documentNumbers)
    {
    }

    public function creating(InvGrn $grn): void
    {
        if (empty($grn->grn_number)) {
            $grn->grn_number = $this->documentNumbers->next(
                InvGrn::class,
                'grn_number',
                config('sfl-inventory.document_prefixes.grn', 'GRN')
            );
        }
    }
}
