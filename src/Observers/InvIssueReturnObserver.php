<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvIssueReturn;
use ME\SflInventory\Services\DocumentNumberService;

class InvIssueReturnObserver
{
    public function __construct(private readonly DocumentNumberService $documentNumbers)
    {
    }

    public function creating(InvIssueReturn $return): void
    {
        if (empty($return->return_no)) {
            $return->return_no = $this->documentNumbers->next(
                InvIssueReturn::class,
                'return_no',
                config('sfl-inventory.document_prefixes.issue_return', 'RET')
            );
        }
    }
}
