{{-- props: report (route-name slug, matches InvReportController::reportMethodMap key) --}}
<div>
    <a href="{{ route('inventory.reports.export', ['report' => $report] + request()->query()) }}" class="btn btn-sm btn-outline-success">
        <i class="fa-solid fa-file-excel"></i> Excel
    </a>
    <a href="{{ url()->current() }}?{{ http_build_query(array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-print"></i> Print
    </a>
</div>
