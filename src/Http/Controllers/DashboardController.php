<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use ME\SflInventory\Models\InvGatePass;
use ME\SflInventory\Models\InvGrn;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvIssue;
use ME\SflInventory\Models\InvPurchaseOrder;
use ME\SflInventory\Models\InvRequisition;
use ME\SflInventory\Models\InvStockAdjustment;
use ME\SflInventory\Models\InvStockTransaction;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\StockService;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('inv_dashboard.view');

        return view('sfl-inventory::admin.dashboard');
    }

    /**
     * Static, try/catch-wrapped so it can be called safely both from this
     * package's own dashboard view AND (per the host app's established
     * pattern for hr::/acc-sfl:: widgets) embedded in the host's aggregate
     * admin dashboard. A failure here must never break that shared page.
     */
    public static function stats(): array
    {
        try {
            $stock = app(StockService::class);
            $today = now()->toDateString();

            $totalItems = InvItem::active()->count();

            $totalStockValue = (float) InvStockTransaction::query()
                ->selectRaw('COALESCE(SUM(CASE WHEN qty_in > 0 THEN value ELSE -value END), 0) as total')
                ->value('total');

            $allLowStockItems = InvItem::active()->with(['category', 'unit'])->orderBy('item_name')->get()
                ->filter(fn (InvItem $item) => $stock->isLowStock($item))
                ->map(function (InvItem $item) use ($stock) {
                    $item->current_stock = $stock->currentStock($item->id);

                    return $item;
                })
                ->values();
            $lowStockItems = $allLowStockItems->take(6);

            $todaysGrnValue = (float) InvGrn::whereDate('receive_date', $today)->sum('total_amount');
            $todaysIssueValue = (float) InvStockTransaction::where('transaction_type', 'issue')
                ->whereDate('transaction_date', $today)
                ->where('qty_out', '>', 0)
                ->sum('value');

            $last30Days = InvStockTransaction::query()
                ->selectRaw("DATE_FORMAT(transaction_date, '%d %b') as label, transaction_date, SUM(qty_in) as qty_in, SUM(qty_out) as qty_out")
                ->where('transaction_date', '>=', now()->subDays(29)->toDateString())
                ->groupBy('transaction_date', 'label')
                ->orderBy('transaction_date')
                ->get();

            $monthlyTrend = collect(range(5, 0))->map(function ($monthsAgo) {
                $month = now()->subMonths($monthsAgo);
                $grnValue = (float) InvGrn::whereYear('receive_date', $month->year)->whereMonth('receive_date', $month->month)->sum('total_amount');
                $issueValue = (float) InvStockTransaction::where('transaction_type', 'issue')
                    ->whereYear('transaction_date', $month->year)->whereMonth('transaction_date', $month->month)
                    ->sum('value');

                return ['label' => $month->format('M Y'), 'grnValue' => $grnValue, 'issueValue' => $issueValue];
            });

            $categoryBreakdown = DB::table('inv_stock_transactions as t')
                ->join('inv_items as i', 'i.id', '=', 't.item_id')
                ->join('inv_item_categories as c', 'c.id', '=', 'i.category_id')
                ->whereNull('i.deleted_at')
                ->whereNull('c.deleted_at')
                ->selectRaw('c.name, SUM(CASE WHEN t.qty_in > 0 THEN t.value ELSE -t.value END) as value')
                ->groupBy('c.id', 'c.name')
                ->havingRaw('SUM(CASE WHEN t.qty_in > 0 THEN t.value ELSE -t.value END) > 0')
                ->orderByDesc('value')
                ->limit(6)
                ->get();

            $storeBalances = InvStore::active()->get()->map(function (InvStore $store) {
                $value = (float) InvStockTransaction::where('store_id', $store->id)
                    ->selectRaw('COALESCE(SUM(CASE WHEN qty_in > 0 THEN value ELSE -value END), 0) as total')
                    ->value('total');

                return (object) ['name' => $store->name, 'value' => $value];
            })->filter(fn ($row) => $row->value > 0)->sortByDesc('value')->take(8)->values();

            $recentGrns = InvGrn::with(['store', 'supplier'])->latest('id')->take(5)->get();
            $recentIssues = InvIssue::with(['store', 'department'])->latest('id')->take(5)->get();
            $recentActivities = InvStockTransaction::with(['item', 'store'])->latest('id')->take(8)->get();

            return [
                'totalItems'          => $totalItems,
                'totalStockValue'     => $totalStockValue,
                'todaysGrnCount'      => InvGrn::whereDate('receive_date', $today)->count(),
                'todaysGrnValue'      => $todaysGrnValue,
                'todaysIssueCount'    => InvIssue::whereDate('issue_date', $today)->count(),
                'todaysIssueValue'    => $todaysIssueValue,
                'pendingRequisitions' => InvRequisition::where('status', 'pending')->count(),
                'pendingPurchaseOrders' => InvPurchaseOrder::where('status', 'draft')->count(),
                'pendingGatePasses'   => InvGatePass::where('status', 'pending')->count(),
                'pendingAdjustments'  => InvStockAdjustment::where('status', 'pending')->count(),
                'lowStockCount'       => $allLowStockItems->count(),
                'lowStockItems'       => $lowStockItems,
                'last30Days'          => $last30Days,
                'monthlyTrend'        => $monthlyTrend,
                'categoryBreakdown'   => $categoryBreakdown,
                'storeBalances'       => $storeBalances,
                'recentGrns'          => $recentGrns,
                'recentIssues'        => $recentIssues,
                'recentActivities'    => $recentActivities,
            ];
        } catch (\Throwable $e) {
            return [
                'totalItems' => 0, 'totalStockValue' => 0, 'todaysGrnCount' => 0, 'todaysGrnValue' => 0,
                'todaysIssueCount' => 0, 'todaysIssueValue' => 0, 'pendingRequisitions' => 0,
                'pendingPurchaseOrders' => 0, 'pendingGatePasses' => 0, 'pendingAdjustments' => 0,
                'lowStockCount' => 0, 'lowStockItems' => collect(), 'last30Days' => collect(),
                'monthlyTrend' => collect(), 'categoryBreakdown' => collect(), 'storeBalances' => collect(),
                'recentGrns' => collect(), 'recentIssues' => collect(), 'recentActivities' => collect(),
            ];
        }
    }
}
