<?php

namespace ME\SflInventory\Observers;

use ME\SflInventory\Models\InvIssue;
use ME\SflInventory\Services\DocumentNumberService;

class InvIssueObserver
{
    public function __construct(private readonly DocumentNumberService $documentNumbers)
    {
    }

    public function creating(InvIssue $issue): void
    {
        if (empty($issue->issue_no)) {
            $issue->issue_no = $this->documentNumbers->next(
                InvIssue::class,
                'issue_no',
                config('sfl-inventory.document_prefixes.issue', 'ISS')
            );
        }
    }
}
