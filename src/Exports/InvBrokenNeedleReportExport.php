<?php

namespace ME\SflInventory\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * One workbook, two tabs — Employee Wise and Machine Wise — so the Excel
 * button on either broken-needle report screen exports the full picture
 * instead of just the tab the user happened to be looking at.
 */
class InvBrokenNeedleReportExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Collection $employeeRows,
        private readonly Collection $machineRows,
        private readonly string $from,
        private readonly string $to,
        private readonly string $sort,
    ) {
    }

    public function sheets(): array
    {
        return [
            new BrokenNeedleEmployeeSheet($this->employeeRows, $this->from, $this->to, $this->sort),
            new BrokenNeedleMachineSheet($this->machineRows, $this->from, $this->to, $this->sort),
        ];
    }
}
