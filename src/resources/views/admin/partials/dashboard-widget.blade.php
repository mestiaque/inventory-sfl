@php
    try {
        $invStats = \ME\SflInventory\Http\Controllers\DashboardController::stats();
    } catch (\Throwable $e) {
        $invStats = null;
    }
@endphp
@if($invStats)
@php
    $s            = $invStats;
    $last30Labels = $s['last30Days']->pluck('label')->toJson();
    $last30In     = $s['last30Days']->pluck('qty_in')->toJson();
    $last30Out    = $s['last30Days']->pluck('qty_out')->toJson();
    $trendLabels  = $s['monthlyTrend']->pluck('label')->toJson();
    $trendGrn     = $s['monthlyTrend']->pluck('grnValue')->toJson();
    $trendIssue   = $s['monthlyTrend']->pluck('issueValue')->toJson();
    $catNames     = $s['categoryBreakdown']->pluck('name')->toJson();
    $catValues    = $s['categoryBreakdown']->pluck('value')->toJson();
    $storeNames   = $s['storeBalances']->pluck('name')->toJson();
    $storeValues  = $s['storeBalances']->pluck('value')->toJson();
    $widgetId     = 'inv_widget_' . uniqid();
@endphp

<style>
.inv-stat-card-link { display: block; text-decoration: none; color: inherit; height: 100%; }
.inv-stat-card { background: #fff; border-radius: 12px; padding: 20px 18px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.07); border: none; transition: transform .2s, box-shadow .2s; height: 100%; }
.inv-stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.11); }
.inv-stat-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.inv-stat-val { font-size: 24px; font-weight: 700; line-height: 1; margin-bottom: 3px; }
.inv-stat-lbl { font-size: 12px; color: #888; font-weight: 500; text-transform: uppercase; letter-spacing: .5px; }
.inv-section-title { font-size: 13px; font-weight: 700; color: #444; text-transform: uppercase; letter-spacing: 1px; border-left: 3px solid #f97316; padding-left: 10px; margin-bottom: 16px; }
.inv-chart-card { background: #fff; border-radius: 12px; padding: 18px 20px; box-shadow: 0 2px 12px rgba(0,0,0,.07); height: 100%; }
.inv-quick-btn { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 10px; background: #fff7ed; border: 1px solid #fde8d0; color: #444; font-size: 13px; font-weight: 500; text-decoration: none; transition: all .2s; }
.inv-quick-btn:hover { background: #f97316; color: #fff; border-color: #f97316; }
.inv-quick-btn i { width: 20px; text-align: center; }
.inv-recent-table td { font-size: 13px; vertical-align: middle; padding: 8px 10px; }
.inv-badge p-1 text-white { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
</style>

{{-- ── Section Header ── --}}
<div class="d-flex align-items-center justify-content-between mb-3 mt-1">
    <h4 class="mb-0" style="font-size:17px;font-weight:700;">
        <i class="fa-solid fa-boxes-stacked me-2" style="color:#f97316;"></i> Inventory Overview
    </h4>
    @if(\Illuminate\Support\Facades\Route::has('inventory.dashboard'))
        <a href="{{ route('inventory.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="font-size:12px;">
            <i class="fa-solid fa-gauge me-1"></i> Inventory Dashboard
        </a>
    @endif
</div>

{{-- ── Stat Cards ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg">
        @if(\Illuminate\Support\Facades\Route::has('inventory.stock-overview.index'))<a href="{{ route('inventory.stock-overview.index') }}" class="inv-stat-card-link">@endif
        <div class="inv-stat-card">
            <div class="inv-stat-icon" style="background:#fff7ed;"><i class="fa-solid fa-boxes-stacked" style="color:#f97316;"></i></div>
            <div>
                <div class="inv-stat-val" style="color:#f97316;">{{ number_format($s['totalItems']) }}</div>
                <div class="inv-stat-lbl">Total Items</div>
            </div>
        </div>
        @if(\Illuminate\Support\Facades\Route::has('inventory.stock-overview.index'))</a>@endif
    </div>
    <div class="col-6 col-md-4 col-lg">
        @if(\Illuminate\Support\Facades\Route::has('inventory.reports.stock-valuation'))<a href="{{ route('inventory.reports.stock-valuation') }}" class="inv-stat-card-link">@endif
        <div class="inv-stat-card">
            <div class="inv-stat-icon" style="background:#ecfdf5;"><i class="fa-solid fa-sack-dollar" style="color:#10b981;"></i></div>
            <div>
                <div class="inv-stat-val" style="color:#10b981;">{{ number_format($s['totalStockValue'], 0) }}</div>
                <div class="inv-stat-lbl">Total Stock Value</div>
            </div>
        </div>
        @if(\Illuminate\Support\Facades\Route::has('inventory.reports.stock-valuation'))</a>@endif
    </div>
    <div class="col-6 col-md-4 col-lg">
        @if(\Illuminate\Support\Facades\Route::has('inventory.grns.index'))<a href="{{ route('inventory.grns.index') }}" class="inv-stat-card-link">@endif
        <div class="inv-stat-card">
            <div class="inv-stat-icon" style="background:#eef2ff;"><i class="fa-solid fa-truck-ramp-box" style="color:#6366f1;"></i></div>
            <div>
                <div class="inv-stat-val" style="color:#6366f1;">{{ number_format($s['todaysGrnCount']) }}</div>
                <div class="inv-stat-lbl">Today's Goods Receive</div>
                <div class="text-muted" style="font-size:11px;">{{ number_format($s['todaysGrnValue'], 0) }} value</div>
            </div>
        </div>
        @if(\Illuminate\Support\Facades\Route::has('inventory.grns.index'))</a>@endif
    </div>
    <div class="col-6 col-md-4 col-lg">
        @if(\Illuminate\Support\Facades\Route::has('inventory.issues.index'))<a href="{{ route('inventory.issues.index') }}" class="inv-stat-card-link">@endif
        <div class="inv-stat-card">
            <div class="inv-stat-icon" style="background:#fffbeb;"><i class="fa-solid fa-dolly" style="color:#f59e0b;"></i></div>
            <div>
                <div class="inv-stat-val" style="color:#f59e0b;">{{ number_format($s['todaysIssueCount']) }}</div>
                <div class="inv-stat-lbl">Today's Issue</div>
                <div class="text-muted" style="font-size:11px;">{{ number_format($s['todaysIssueValue'], 0) }} value</div>
            </div>
        </div>
        @if(\Illuminate\Support\Facades\Route::has('inventory.issues.index'))</a>@endif
    </div>
    <div class="col-6 col-md-4 col-lg">
        @if(\Illuminate\Support\Facades\Route::has('inventory.reports.low-stock'))<a href="{{ route('inventory.reports.low-stock') }}" class="inv-stat-card-link">@endif
        <div class="inv-stat-card">
            <div class="inv-stat-icon" style="background:#fff1f2;"><i class="fa-solid fa-triangle-exclamation" style="color:#f43f5e;"></i></div>
            <div>
                <div class="inv-stat-val" style="color:#f43f5e;">{{ number_format($s['lowStockCount']) }}</div>
                <div class="inv-stat-lbl">Low Stock Items</div>
            </div>
        </div>
        @if(\Illuminate\Support\Facades\Route::has('inventory.reports.low-stock'))</a>@endif
    </div>
</div>

{{-- ── Pending Approvals Row ── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="inv-chart-card">
            <div class="inv-section-title">Stock Movement – Last 30 Days</div>
            <div id="{{ $widgetId }}_movement" style="height:220px;"></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="inv-chart-card h-100">
            <div class="inv-section-title">Pending Approvals</div>
            <div class="row g-2 text-center">
                <div class="col-6">
                    <div class="inv-stat-val" style="color:#f59e0b;font-size:20px;">{{ $s['pendingRequisitions'] }}</div>
                    <div class="inv-stat-lbl">Requisitions</div>
                </div>
                <div class="col-6">
                    <div class="inv-stat-val" style="color:#6366f1;font-size:20px;">{{ $s['pendingPurchaseOrders'] }}</div>
                    <div class="inv-stat-lbl">Purchase Orders</div>
                </div>
                <div class="col-6 mt-3">
                    <div class="inv-stat-val" style="color:#f43f5e;font-size:20px;">{{ $s['pendingGatePasses'] }}</div>
                    <div class="inv-stat-lbl">Gate Passes</div>
                </div>
                <div class="col-6 mt-3">
                    <div class="inv-stat-val" style="color:#10b981;font-size:20px;">{{ $s['pendingAdjustments'] }}</div>
                    <div class="inv-stat-lbl">Adjustments</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Charts Row 2 ── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="inv-chart-card">
            <div class="inv-section-title">Stock Value by Category</div>
            <div id="{{ $widgetId }}_category" style="height:230px;"></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="inv-chart-card">
            <div class="inv-section-title">6-Month GRN vs Issue Value</div>
            <div id="{{ $widgetId }}_trend" style="height:230px;"></div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="inv-chart-card">
            <div class="inv-section-title">Quick Links</div>
            <div class="d-flex flex-column gap-2">
                @can('inv_item.list')<a href="{{ route('inventory.items.index') }}" class="inv-quick-btn"><i class="fa-solid fa-shirt"></i> Items</a>@endcan
                @can('inv_requisition.list')<a href="{{ route('inventory.requisitions.index') }}" class="inv-quick-btn"><i class="fa-solid fa-file-pen"></i> Requisitions</a>@endcan
                @can('inv_stock_ledger.view')<a href="{{ route('inventory.stock-ledger.index') }}" class="inv-quick-btn"><i class="fa-solid fa-book"></i> Stock Ledger</a>@endcan
                @can('inv_report.view')<a href="{{ route('inventory.reports.current-stock') }}" class="inv-quick-btn"><i class="fa-solid fa-chart-line"></i> Reports</a>@endcan
            </div>
        </div>
    </div>
</div>

{{-- ── Insights Row ── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="inv-chart-card h-100">
            <div class="inv-section-title">Stock by Store (Top 8)</div>
            <div id="{{ $widgetId }}_stores" style="height:230px;"></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="inv-chart-card h-100">
            <div class="inv-section-title">Low Stock Alerts</div>
            @if($s['lowStockItems']->isNotEmpty())
                <div class="d-flex flex-column gap-2">
                    @foreach($s['lowStockItems'] as $item)
                        <div class="d-flex align-items-center justify-content-between" style="font-size:13px;">
                            <span>{{ $item->item_name }} <span class="text-muted">({{ $item->item_code }})</span></span>
                            <span class="inv-badge p-1 text-white" style="background:#fff1f2;color:#f43f5e;">{{ inv_qty($item->current_stock) }} / {{ inv_qty($item->minimum_stock) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-muted text-center py-4" style="font-size:13px;">No items below minimum stock</div>
            @endif
        </div>
    </div>
    <div class="col-lg-4">
        <div class="inv-chart-card h-100">
            <div class="inv-section-title">Recent Issues</div>
            @if($s['recentIssues']->isNotEmpty())
                <div class="d-flex flex-column gap-2">
                    @foreach($s['recentIssues'] as $issue)
                        <div class="d-flex align-items-center justify-content-between" style="font-size:13px;">
                            <span>{{ $issue->issue_no }} <span class="text-muted">({{ $issue->department?->name }})</span></span>
                            <span class="inv-badge p-1 text-white" style="background:#fffbeb;color:#f59e0b;">{{ $issue->issue_date?->format('d M') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-muted text-center py-4" style="font-size:13px;">No issues yet</div>
            @endif
        </div>
    </div>
</div>

{{-- ── Recent Activity & Recent GRNs ── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="inv-chart-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="inv-section-title mb-0">Recent Stock Activity</div>
                @if(\Illuminate\Support\Facades\Route::has('inventory.stock-ledger.index'))
                    <a href="{{ route('inventory.stock-ledger.index') }}" style="font-size:12px;color:#f97316;text-decoration:none;">View All &rarr;</a>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background:#f8f9ff;">
                        <tr>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Date</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Item</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Type</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Store</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($s['recentActivities'] as $activity)
                            <tr>
                                <td class="inv-recent-table">{{ $activity->transaction_date?->format('d M Y') }}</td>
                                <td class="inv-recent-table">{{ $activity->item?->item_code }}</td>
                                <td class="inv-recent-table">{{ ucwords(str_replace('_', ' ', $activity->transaction_type)) }}</td>
                                <td class="inv-recent-table">{{ $activity->store?->name }}</td>
                                <td class="inv-recent-table {{ $activity->qty_in > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $activity->qty_in > 0 ? '+' . inv_qty($activity->qty_in) : '-' . inv_qty($activity->qty_out) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No recent activity.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="inv-chart-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="inv-section-title mb-0">Recent GRNs</div>
                @if(\Illuminate\Support\Facades\Route::has('inventory.grns.index'))
                    <a href="{{ route('inventory.grns.index') }}" style="font-size:12px;color:#f97316;text-decoration:none;">View All &rarr;</a>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background:#f8f9ff;">
                        <tr>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">GRN No</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Supplier</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Store</th>
                            <th style="font-size:12px;color:#888;font-weight:600;border:none;padding:8px 10px;">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($s['recentGrns'] as $grn)
                            <tr>
                                <td class="inv-recent-table" style="font-weight:600;color:#f97316;">{{ $grn->grn_number }}</td>
                                <td class="inv-recent-table">{{ $grn->supplier?->name }}</td>
                                <td class="inv-recent-table">{{ $grn->store?->name }}</td>
                                <td class="inv-recent-table">{{ number_format($grn->total_amount, 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No GRNs yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── ApexCharts ── --}}
@push('js')
<script>
(function() {
    var last30Labels = {!! $last30Labels !!};
    var last30In = {!! $last30In !!};
    var last30Out = {!! $last30Out !!};
    var trendLabels = {!! $trendLabels !!};
    var trendGrn = {!! $trendGrn !!};
    var trendIssue = {!! $trendIssue !!};
    var catNames = {!! $catNames !!};
    var catValues = {!! $catValues !!};
    var storeNames = {!! $storeNames !!};
    var storeValues = {!! $storeValues !!};

    function initCharts() {
        new ApexCharts(document.getElementById('{{ $widgetId }}_movement'), {
            series: [
                { name: 'Qty In', data: last30In },
                { name: 'Qty Out', data: last30Out },
            ],
            chart: { type: 'area', height: 220, toolbar: { show: false } },
            colors: ['#10b981', '#f43f5e'],
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .35, opacityTo: .05 } },
            xaxis: { categories: last30Labels, labels: { rotate: -45, style: { fontSize: '10px' } }, tickAmount: 10 },
            yaxis: { labels: { style: { fontSize: '11px' } } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            legend: { fontSize: '12px' },
        }).render();

        new ApexCharts(document.getElementById('{{ $widgetId }}_category'), {
            series: catValues,
            chart: { type: 'donut', height: 230 },
            labels: catNames,
            colors: ['#f97316', '#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#0ea5e9'],
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%' },
            plotOptions: { pie: { donut: { size: '65%' } } },
            stroke: { width: 0 },
        }).render();

        new ApexCharts(document.getElementById('{{ $widgetId }}_trend'), {
            series: [
                { name: 'GRN Value', data: trendGrn },
                { name: 'Issue Value', data: trendIssue },
            ],
            chart: { type: 'bar', height: 230, toolbar: { show: false } },
            plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
            colors: ['#6366f1', '#f59e0b'],
            xaxis: { categories: trendLabels, labels: { style: { fontSize: '11px' } } },
            yaxis: { labels: { style: { fontSize: '11px' } } },
            dataLabels: { enabled: false },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
            legend: { fontSize: '12px' },
        }).render();

        new ApexCharts(document.getElementById('{{ $widgetId }}_stores'), {
            series: [{ name: 'Stock Value', data: storeValues }],
            chart: { type: 'bar', height: 230, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
            colors: ['#f97316'],
            xaxis: { categories: storeNames, labels: { style: { fontSize: '11px' } } },
            dataLabels: { enabled: true, style: { fontSize: '11px' } },
            grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
        }).render();
    }

    if (typeof ApexCharts !== 'undefined') {
        initCharts();
    } else {
        var scriptTag = document.createElement('script');
        scriptTag.src = '{{ asset("admin/assets/js/apexcharts/apexcharts.min.js") }}';
        scriptTag.onload = initCharts;
        document.head.appendChild(scriptTag);
    }
})();
</script>
@endpush
@endif
