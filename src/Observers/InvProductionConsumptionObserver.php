<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvProductionConsumption;
use ME\SflInventory\Services\DocumentNumberService;

class InvProductionConsumptionObserver
{
    public function __construct(private readonly DocumentNumberService $documentNumbers)
    {
    }

    public function creating(InvProductionConsumption $consumption): void
    {
        if (empty($consumption->consumption_no)) {
            $consumption->consumption_no = $this->documentNumbers->next(
                InvProductionConsumption::class,
                'consumption_no',
                config('sfl-inventory.document_prefixes.consumption', 'CONS')
            );
        }
    }
}
