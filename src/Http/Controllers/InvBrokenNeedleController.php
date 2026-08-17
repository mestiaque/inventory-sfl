<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\SflInventory\Exports\InvBrokenNeedleReportExport;
use ME\SflInventory\Exports\InvReportExport;
use ME\SflInventory\Http\Requests\InvBrokenNeedleRequest;
use ME\SflInventory\Models\InvBrokenNeedle;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Models\InvDepartment;
use ME\SflInventory\Models\InvMachine;

class InvBrokenNeedleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_broken_needle.list');

        $entries = InvBrokenNeedle::query()
            ->with(['employee', 'department', 'machine', 'buyer', 'creator'])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('machine_id'), fn ($q) => $q->where('machine_id', $request->machine_id))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('line_no'), fn ($q) => $q->where('line_no', 'like', '%' . $request->line_no . '%'))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('broken_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('broken_date', '<=', $request->date_to))
            ->latest('broken_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sfl-inventory::admin.broken-needles.index', compact('entries') + $this->formOptions());
    }

    public function store(InvBrokenNeedleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['line_no'] = InvMachine::find($data['machine_id'])?->line;
        $data['created_by'] = auth()->id();
        InvBrokenNeedle::create($data);

        return back()->with('success', 'Broken needle entry recorded.');
    }

    public function update(InvBrokenNeedleRequest $request, InvBrokenNeedle $broken_needle): RedirectResponse
    {
        $data = $request->validated();
        $data['line_no'] = InvMachine::find($data['machine_id'])?->line;
        $broken_needle->update($data);

        return back()->with('success', 'Broken needle entry updated.');
    }

    public function destroy(InvBrokenNeedle $broken_needle): RedirectResponse
    {
        $this->authorize('inv_broken_needle.delete');

        $broken_needle->delete();

        return back()->with('success', 'Broken needle entry deleted.');
    }

    /**
     * Nothing else references a broken-needle entry (it's a leaf record —
     * no stock ledger impact, no child documents), so force delete is just
     * a permanent version of the same delete.
     */
    public function forceDestroy(InvBrokenNeedle $broken_needle): RedirectResponse
    {
        $this->authorize('inv_broken_needle.force_delete');

        $broken_needle->forceDelete();

        return back()->with('success', 'Broken needle entry permanently deleted.');
    }

    /**
     * Digital match of the factory's paper "Daily Broken Needle Report"
     * slip. On the paper form, one row is shared by up to 5 breaks from the
     * same operator on the same line/needle-type/size/machine that day —
     * each repeat just gets an extra tally mark under 1st/2nd/3rd/4th/5th
     * instead of a new row — and only overflows into another row once a
     * 6th one happens (that new row doesn't need to be physically adjacent
     * on paper, but here it's simply the next generated row). So entries
     * are grouped by (employee, line, needle type/size, machine) and
     * chunked into batches of 5 before rendering.
     */
    public function dailyReport(Request $request): View
    {
        $this->authorize('inv_broken_needle.view');

        $date = $request->filled('date') ? $request->date : now()->toDateString();

        $entries = InvBrokenNeedle::query()
            ->with(['employee', 'department', 'machine', 'buyer'])
            ->whereDate('broken_date', $date)
            ->when($request->filled('line_no'), fn ($q) => $q->where('line_no', 'like', '%' . $request->line_no . '%'))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->orderBy('line_no')
            ->orderBy('id')
            ->get();

        $rows = $entries
            ->groupBy(fn ($e) => implode('|', [$e->employee_id, $e->line_no, $e->needle_type, $e->needle_size, $e->machine_id]))
            ->flatMap(fn ($group) => $group->chunk(5))
            ->map(function ($chunk) {
                $first = $chunk->first();

                return (object) [
                    'line_no'     => $first->line_no,
                    'needle_type' => $first->needle_type,
                    'needle_size' => $first->needle_size,
                    'machine'     => $first->machine,
                    'employee'    => $first->employee,
                    'employee_id' => $first->employee_id,
                    'remarks'     => $chunk->pluck('remarks')->filter()->unique()->implode('; '),
                    'occurrences' => $chunk->count(),
                ];
            })
            ->values();

        $printMode = request()->boolean('print');

        return view('sfl-inventory::admin.broken-needles.daily-report',
            compact('entries', 'rows', 'date', 'printMode') + $this->formOptions());
    }

    public function exportDailyReport(Request $request)
    {
        $request->merge(['print' => 1, 'excel_export' => 1]);

        $view = $this->dailyReport($request)->with('printMode', true);
        $date = $request->filled('date') ? $request->date : now()->toDateString();

        return Excel::download(new InvReportExport($view, 'Daily Needle Supply'), "daily-needle-supply-report-{$date}.xlsx");
    }

    /**
     * Month-end summary: total broken-needle qty per employee, sortable
     * high-to-low (default) or low-to-high. Defaults to the current month.
     */
    public function report(Request $request): View
    {
        $this->authorize('inv_broken_needle.view');

        $from = $request->filled('date_from') ? $request->date_from : now()->startOfMonth()->toDateString();
        $to = $request->filled('date_to') ? $request->date_to : now()->endOfMonth()->toDateString();
        $sort = $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $employeeId = $request->filled('employee_id') ? (int) $request->employee_id : null;

        $rows = $this->employeeRows($from, $to, $sort, $employeeId);
        $printMode = request()->boolean('print');

        return view('sfl-inventory::admin.broken-needles.report',
            compact('rows', 'from', 'to', 'sort', 'employeeId', 'printMode') + $this->formOptions());
    }

    /**
     * Machine-wise summary: total broken-needle qty per machine (plus a
     * "Not Specified" bucket for entries recorded before machine tracking
     * existed). Selecting a single machine drills down into a per-employee
     * breakdown for that machine instead.
     */
    public function machineReport(Request $request): View
    {
        $this->authorize('inv_broken_needle.view');

        $from = $request->filled('date_from') ? $request->date_from : now()->startOfMonth()->toDateString();
        $to = $request->filled('date_to') ? $request->date_to : now()->endOfMonth()->toDateString();
        $sort = $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $departmentId = $request->filled('department_id') ? (int) $request->department_id : null;
        $machineId = $request->filled('machine_id') ? (int) $request->machine_id : null;

        $machine = null;

        if ($machineId) {
            $rows = $this->machineEmployeeBreakdownRows($from, $to, $sort, $departmentId, $machineId);
            $machine = InvMachine::with('department')->find($machineId);
        } else {
            $rows = $this->machineRows($from, $to, $sort, $departmentId);
        }

        $printMode = request()->boolean('print');

        return view('sfl-inventory::admin.broken-needles.machine-report',
            compact('rows', 'from', 'to', 'sort', 'departmentId', 'machineId', 'machine', 'printMode') + $this->formOptions());
    }

    /**
     * One Excel workbook, two tabs (Employee Wise + Machine Wise) — the
     * machine tab is always the full overview, ignoring any single-machine
     * drill-down the Machine Report screen might currently be showing.
     */
    public function exportCombinedReport(Request $request)
    {
        $from = $request->filled('date_from') ? $request->date_from : now()->startOfMonth()->toDateString();
        $to = $request->filled('date_to') ? $request->date_to : now()->endOfMonth()->toDateString();
        $sort = $request->get('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $departmentId = $request->filled('department_id') ? (int) $request->department_id : null;
        $employeeId = $request->filled('employee_id') ? (int) $request->employee_id : null;

        $employeeRows = $this->employeeRows($from, $to, $sort, $employeeId);
        $machineRows = $this->machineRows($from, $to, $sort, $departmentId);

        return Excel::download(
            new InvBrokenNeedleReportExport($employeeRows, $machineRows, $from, $to, $sort),
            'broken-needle-report.xlsx'
        );
    }

    private function employeeRows(string $from, string $to, string $sort, ?int $employeeId = null)
    {
        $rows = DB::table('inv_broken_needles')
            ->whereDate('broken_date', '>=', $from)
            ->whereDate('broken_date', '<=', $to)
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->select('employee_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('COUNT(*) as incidents'))
            ->groupBy('employee_id')
            ->orderBy('total_qty', $sort)
            ->get();

        $employees = class_exists(\ME\Hr\Models\HrEmployee::class)
            ? \ME\Hr\Models\HrEmployee::whereIn('id', $rows->pluck('employee_id'))->get()->keyBy('id')
            : collect();

        return $rows->map(function ($row) use ($employees) {
            $row->employee = $employees->get($row->employee_id);

            return $row;
        });
    }

    private function machineRows(string $from, string $to, string $sort, ?int $departmentId)
    {
        $rows = DB::table('inv_broken_needles')
            ->whereDate('broken_date', '>=', $from)
            ->whereDate('broken_date', '<=', $to)
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->select('machine_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('COUNT(*) as incidents'))
            ->groupBy('machine_id')
            ->orderBy('total_qty', $sort)
            ->get();

        $machines = InvMachine::with('department')->whereIn('id', $rows->pluck('machine_id')->filter())->get()->keyBy('id');

        return $rows->map(function ($row) use ($machines) {
            $row->machine = $row->machine_id ? $machines->get($row->machine_id) : null;

            return $row;
        });
    }

    private function machineEmployeeBreakdownRows(string $from, string $to, string $sort, ?int $departmentId, int $machineId)
    {
        $rows = DB::table('inv_broken_needles')
            ->whereDate('broken_date', '>=', $from)
            ->whereDate('broken_date', '<=', $to)
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->where('machine_id', $machineId)
            ->select('employee_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('COUNT(*) as incidents'))
            ->groupBy('employee_id')
            ->orderBy('total_qty', $sort)
            ->get();

        $employees = class_exists(\ME\Hr\Models\HrEmployee::class)
            ? \ME\Hr\Models\HrEmployee::whereIn('id', $rows->pluck('employee_id'))->get()->keyBy('id')
            : collect();

        return $rows->map(function ($row) use ($employees) {
            $row->employee = $employees->get($row->employee_id);

            return $row;
        });
    }

    private function formOptions(): array
    {
        $employees = class_exists(\ME\Hr\Models\HrEmployee::class)
            ? \ME\Hr\Models\HrEmployee::query()->where('status', 1)->orderBy('name')->get()
            : collect();

        return [
            'employees'   => $employees,
            'departments' => InvDepartment::active()->orderBy('name')->get(),
            'machines'    => InvMachine::active()->orderBy('name')->get(),
            'buyers'      => InvBuyer::active()->orderBy('name')->get(),
        ];
    }
}
