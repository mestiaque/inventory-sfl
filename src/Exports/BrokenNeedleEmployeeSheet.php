<?php

namespace ME\SflInventory\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use ME\SflInventory\Exports\Concerns\StylesReportSheet;

class BrokenNeedleEmployeeSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    use StylesReportSheet;

    public function __construct(
        private readonly Collection $rows,
        private readonly string $from,
        private readonly string $to,
        private readonly string $sort,
    ) {
    }

    public function array(): array
    {
        $data = [
            ['Broken Needle — Employee Wise Report'],
            [$this->periodLabel()],
            [''],
            ['#', 'Employee ID', 'Employee Name', 'Incidents', 'Total Broken Needle Qty'],
        ];

        foreach ($this->rows as $i => $row) {
            $data[] = [
                $i + 1,
                $row->employee?->employee_id ?? $row->employee_id,
                $row->employee?->name ?? '—',
                (int) $row->incidents,
                (int) $row->total_qty,
            ];
        }

        $data[] = ['', '', 'Grand Total', (int) $this->rows->sum('incidents'), (int) $this->rows->sum('total_qty')];

        return $data;
    }

    public function title(): string
    {
        return 'Employee Wise';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = 4 + $this->rows->count() + 1;
                $this->applyReportStyles($event->sheet->getDelegate(), 5, 4, $lastRow);
            },
        ];
    }

    private function periodLabel(): string
    {
        $range = Carbon::parse($this->from)->format('d M Y') . ' – ' . Carbon::parse($this->to)->format('d M Y');
        $sortLabel = $this->sort === 'asc' ? 'low to high' : 'high to low';

        return "{$range}, sorted {$sortLabel}";
    }
}
