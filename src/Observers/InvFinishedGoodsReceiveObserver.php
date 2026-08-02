<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvFinishedGoodsReceive;
use ME\SflInventory\Services\DocumentNumberService;

class InvFinishedGoodsReceiveObserver
{
    public function __construct(private readonly DocumentNumberService $documentNumbers)
    {
    }

    public function creating(InvFinishedGoodsReceive $receive): void
    {
        if (empty($receive->receive_no)) {
            $receive->receive_no = $this->documentNumbers->next(
                InvFinishedGoodsReceive::class,
                'receive_no',
                config('sfl-inventory.document_prefixes.fg_receive', 'FGR')
            );
        }
    }
}
