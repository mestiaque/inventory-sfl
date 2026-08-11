<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\SflInventory\Exports\InvReportExport;
use ME\SflInventory\Http\Requests\InvBrokenNeedleRequest;
use ME\SflInventory\Models\InvBrokenNeedle;
use ME\SflInventory\Models\InvDepartment;

class InvBrokenNeedleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_broken_needle.list');

        $entries = InvBrokenNeedle::query()
            ->with(['employee', 'department', 'creator'])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
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
        $data['created_by'] = auth()->id();
        InvBrokenNeedle::create($data);

        return back()->with('success', 'Broken needle entry recorded.');
    }

    public function update(InvBrokenNeedleRequest $request, InvBrokenNeedle $broken_needle): RedirectResponse
    {
        $broken_needle->update($request->validated());

        return back()->with('success', 'Broken needle entry updated.');
    }

    public function destroy(InvBrokenNeedle $broken_needle): RedirectResponse
    {
        $this->authorize('inv_broken_needle.delete');

        $broken_needle->delete();

        return back()->with('success', 'Broken needle entry deleted.');
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

        $rows = DB::table('inv_broken_needles')
            ->whereDate('broken_date', '>=', $from)
            ->whereDate('broken_date', '<=', $to)
            ->select('employee_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('COUNT(*) as incidents'))
            ->groupBy('employee_id')
            ->orderBy('total_qty', $sort)
            ->get();

        $employees = class_exists(\ME\Hr\Models\HrEmployee::class)
            ? \ME\Hr\Models\HrEmployee::whereIn('id', $rows->pluck('employee_id'))->get()->keyBy('id')
            : collect();

        $rows = $rows->map(function ($row) use ($employees) {
            $row->employee = $employees->get($row->employee_id);

            return $row;
        });

        $printMode = request()->boolean('print');

        return view('sfl-inventory::admin.broken-needles.report', compact('rows', 'from', 'to', 'sort', 'printMode'));
    }

    public function exportReport(Request $request)
    {
        $request->merge(['print' => 1, 'excel_export' => 1]);

        $view = $this->report($request)->with('printMode', true);

        return Excel::download(new InvReportExport($view, 'broken-needle'), 'broken-needle-report.xlsx');
    }

    private function formOptions(): array
    {
        $employees = class_exists(\ME\Hr\Models\HrEmployee::class)
            ? \ME\Hr\Models\HrEmployee::query()->where('status', 1)->orderBy('name')->get()
            : collect();

        return [
            'employees'   => $employees,
            'departments' => InvDepartment::active()->orderBy('name')->get(),
        ];
    }
}
