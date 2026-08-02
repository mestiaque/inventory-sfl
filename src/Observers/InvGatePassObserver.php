<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvGatePass;
use ME\SflInventory\Services\DocumentNumberService;

class InvGatePassObserver
{
    public function __construct(private readonly DocumentNumberService $documentNumbers)
    {
    }

    public function creating(InvGatePass $gatePass): void
    {
        if (empty($gatePass->gate_pass_no)) {
            $gatePass->gate_pass_no = $this->documentNumbers->next(
                InvGatePass::class,
                'gate_pass_no',
                config('sfl-inventory.document_prefixes.gate_pass', 'GP')
            );
        }
    }
}
