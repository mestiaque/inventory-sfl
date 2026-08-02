<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvStockTransfer;
use ME\SflInventory\Services\DocumentNumberService;

class InvStockTransferObserver
{
    public function __construct(private readonly DocumentNumberService $documentNumbers)
    {
    }

    public function creating(InvStockTransfer $transfer): void
    {
        if (empty($transfer->transfer_no)) {
            $transfer->transfer_no = $this->documentNumbers->next(
                InvStockTransfer::class,
                'transfer_no',
                config('sfl-inventory.document_prefixes.transfer', 'TRF')
            );
        }
    }
}
