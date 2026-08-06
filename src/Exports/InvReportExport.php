<?php

namespace ME\SflInventory\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Generic Excel export for every inventory report — wraps an already-built
 * View instance (the same one the screen/print output uses, just with
 * printMode=true so filter forms/buttons/pagination are excluded) rather
 * than duplicating each report's query logic in a second export class.
 */
class InvReportExport implements FromView, WithTitle
{
    public function __construct(private readonly View $reportView, private readonly string $sheetTitle = 'Report')
    {
    }

    public function view(): View
    {
        return $this->reportView;
    }

    /**
     * Excel worksheet names are hard-capped at 31 characters — the page's
     * own <title> (e.g. "Store Inventory Report - Suhana Fashions Ltd")
     * blows past that, so the sheet name is set explicitly instead.
     */
    public function title(): string
    {
        return mb_substr($this->sheetTitle, 0, 31);
    }
}
