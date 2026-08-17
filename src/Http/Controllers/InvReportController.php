<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\SflInventory\Exports\InvReportExport;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Models\InvDepartment;
use ME\SflInventory\Models\InvGatePass;
use ME\SflInventory\Models\InvGrn;
use ME\SflInventory\Models\InvGrnItem;
use ME\SflInventory\Models\InvIssue;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvItemCategory;
use ME\SflInventory\Models\InvShipment;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Models\InvSupplier;
use ME\SflInventory\Services\StockService;

/**
 * One controller for all 13 report types (module 20). Every action is gated
 * by the single 'inv_report.view' permission and shares the same filter
 * dimensions (date range / store / category / department / supplier / buyer
 * / item / status) where applicable to that report.
 */
class InvReportController extends Controller
{
    public function __construct(private readonly StockService $stock)
    {
    }

    public function currentStock(Request $request): View
    {
        $this->authorize('inv_report.view');

        $items = InvItem::query()
            ->with(['category', 'unit'])
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('item_id'), fn ($q) => $q->where('id', $request->item_id))
            ->active()
            ->orderBy('item_name')
            ->get()
            ->map(function (InvItem $item) {
                $item->current_stock = $this->stock->currentStock($item->id);
                $item->stock_value = $this->stock->stockValue($item->id);

                return $item;
            })
            ->filter(fn ($item) => $item->current_stock != 0);

        $categories = InvItemCategory::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.current-stock', compact('items', 'categories'));
    }

    public function stockSummary(Request $request): View
    {
        $this->authorize('inv_report.view');

        $summary = InvItemCategory::active()->orderBy('name')->get()->map(function (InvItemCategory $category) {
            $itemIds = InvItem::where('category_id', $category->id)->pluck('id');
            $qty = 0.0;
            $value = 0.0;
            foreach ($itemIds as $itemId) {
                $qty += $this->stock->currentStock($itemId);
                $value += $this->stock->stockValue($itemId);
            }

            return (object) ['category' => $category, 'items_count' => $itemIds->count(), 'total_qty' => $qty, 'total_value' => $value];
        })->filter(fn ($row) => $row->total_qty != 0);

        return view('sfl-inventory::admin.reports.stock-summary', compact('summary'));
    }

    public function itemHistory(Request $request): View
    {
        $this->authorize('inv_report.view');

        $items = InvItem::active()->orderBy('item_name')->get();
        $selectedItem = $request->filled('item_id') ? InvItem::find($request->item_id) : null;

        $from = $request->filled('date_from') ? $request->date_from : now()->startOfMonth()->toDateString();
        $to = $request->filled('date_to') ? $request->date_to : now()->toDateString();

        $transactions = \ME\SflInventory\Models\InvStockTransaction::query()
            ->with(['store', 'item'])
            ->when($selectedItem, fn ($q) => $q->where('item_id', $selectedItem->id))
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->paginate($request->boolean('print') ? 100000 : 50)
            ->withQueryString();

        return view('sfl-inventory::admin.reports.item-history', compact('items', 'transactions', 'selectedItem', 'from', 'to'));
    }

    public function storeWiseStock(Request $request): View
    {
        $this->authorize('inv_report.view');

        $stores = InvStore::active()->orderBy('name')->get()->map(function (InvStore $store) {
            $itemIds = DB::table('inv_stock_transactions')->where('store_id', $store->id)->distinct()->pluck('item_id');
            $value = 0.0;
            foreach ($itemIds as $itemId) {
                $value += $this->stock->stockValue($itemId, $store->id);
            }

            return (object) ['store' => $store, 'items_count' => $itemIds->count(), 'total_value' => $value];
        });

        return view('sfl-inventory::admin.reports.store-wise-stock', compact('stores'));
    }

    public function departmentWiseConsumption(Request $request): View
    {
        $this->authorize('inv_report.view');

        $rows = DB::table('inv_production_consumption_items as pci')
            ->join('inv_production_consumptions as pc', 'pc.id', '=', 'pci.consumption_id')
            ->join('inv_departments as d', 'd.id', '=', 'pc.department_id')
            ->whereNull('pc.deleted_at')
            ->whereNull('d.deleted_at')
            ->when($request->filled('department_id'), fn ($q) => $q->where('pc.department_id', $request->department_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('pc.consumption_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('pc.consumption_date', '<=', $request->date_to))
            ->groupBy('d.id', 'd.name')
            ->select('d.name as department_name', DB::raw('SUM(pci.consumed_qty) as total_consumed'), DB::raw('SUM(pci.waste_qty) as total_waste'))
            ->orderBy('d.name')
            ->get();

        $departments = InvDepartment::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.department-consumption', compact('rows', 'departments'));
    }

    public function supplierWisePurchase(Request $request): View
    {
        $this->authorize('inv_report.view');

        $rows = DB::table('inv_grn_items as gi')
            ->join('inv_grns as g', 'g.id', '=', 'gi.grn_id')
            ->join('inv_suppliers as s', 's.id', '=', 'g.supplier_id')
            ->whereNull('g.deleted_at')
            ->whereNull('s.deleted_at')
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('g.supplier_id', $request->supplier_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('g.receive_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('g.receive_date', '<=', $request->date_to))
            ->groupBy('s.id', 's.name')
            ->select('s.name as supplier_name', DB::raw('SUM(gi.received_qty) as total_qty'), DB::raw('SUM(gi.amount) as total_amount'))
            ->orderByDesc('total_amount')
            ->get();

        $suppliers = InvSupplier::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.supplier-purchase', compact('rows', 'suppliers'));
    }

    public function grnReport(Request $request): View
    {
        $this->authorize('inv_report.view');

        $grns = InvGrn::query()
            ->with(['store', 'supplier', 'purchaseOrder'])
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('receive_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('receive_date', '<=', $request->date_to))
            ->latest('receive_date')
            ->paginate($request->boolean('print') ? 100000 : 30)
            ->withQueryString();

        $stores = InvStore::active()->orderBy('name')->get();
        $suppliers = InvSupplier::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.grn-report', compact('grns', 'stores', 'suppliers'));
    }

    /**
     * One row per received line (not per GRN document), with the audit trail
     * fields the document-level GRN Report doesn't carry: which system user
     * created the entry and when, alongside the physical receive date.
     */
    public function grnItemWiseReport(Request $request): View
    {
        $this->authorize('inv_report.view');

        $lines = InvGrnItem::query()
            ->select('inv_grn_items.*')
            ->join('inv_grns', 'inv_grns.id', '=', 'inv_grn_items.grn_id')
            ->whereNull('inv_grns.deleted_at')
            ->with(['item.unit', 'grn.store', 'grn.supplier', 'grn.buyer', 'grn.purchaseOrder', 'grn.creator'])
            ->when($request->filled('item_id'), fn ($q) => $q->where('inv_grn_items.item_id', $request->item_id))
            ->when($request->filled('store_id'), fn ($q) => $q->where('inv_grns.store_id', $request->store_id))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('inv_grns.supplier_id', $request->supplier_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('inv_grns.receive_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('inv_grns.receive_date', '<=', $request->date_to))
            ->orderByDesc('inv_grns.receive_date')
            ->orderByDesc('inv_grn_items.id')
            ->paginate($request->boolean('print') ? 100000 : 30)
            ->withQueryString();

        $items = InvItem::active()->orderBy('item_name')->get();
        $stores = InvStore::active()->orderBy('name')->get();
        $suppliers = InvSupplier::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.grn-item-wise-report', compact('lines', 'items', 'stores', 'suppliers'));
    }

    public function issueReport(Request $request): View
    {
        $this->authorize('inv_report.view');

        $issues = InvIssue::query()
            ->with(['store', 'department'])
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('issue_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('issue_date', '<=', $request->date_to))
            ->latest('issue_date')
            ->paginate($request->boolean('print') ? 100000 : 30)
            ->withQueryString();

        $departments = InvDepartment::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.issue-report', compact('issues', 'departments'));
    }

    public function gatePassReport(Request $request): View
    {
        $this->authorize('inv_report.view');

        $gatePasses = InvGatePass::query()
            ->with(['buyer', 'store'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('gate_pass_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('gate_pass_date', '<=', $request->date_to))
            ->latest('gate_pass_date')
            ->paginate($request->boolean('print') ? 100000 : 30)
            ->withQueryString();

        return view('sfl-inventory::admin.reports.gate-pass-report', compact('gatePasses'));
    }

    public function shipmentReport(Request $request): View
    {
        $this->authorize('inv_report.view');

        $shipments = InvShipment::query()
            ->with(['buyer', 'gatePass', 'gatePasses'])
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('shipment_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('shipment_date', '<=', $request->date_to))
            ->latest('shipment_date')
            ->paginate($request->boolean('print') ? 100000 : 30)
            ->withQueryString();

        $buyers = InvBuyer::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.shipment-report', compact('shipments', 'buyers'));
    }

    public function lowStock(): View
    {
        $this->authorize('inv_report.view');

        $items = InvItem::active()->with(['category', 'unit'])->orderBy('item_name')->get()
            ->filter(fn (InvItem $item) => $this->stock->isLowStock($item))
            ->map(function (InvItem $item) {
                $item->current_stock = $this->stock->currentStock($item->id);

                return $item;
            });

        return view('sfl-inventory::admin.reports.low-stock', compact('items'));
    }

    public function deadStock(): View
    {
        $this->authorize('inv_report.view');

        $items = InvItem::active()->with(['category', 'unit'])->orderBy('item_name')->get()
            ->filter(fn (InvItem $item) => $this->stock->isDeadStock($item))
            ->map(function (InvItem $item) {
                $item->current_stock = $this->stock->currentStock($item->id);
                $item->stock_value = $this->stock->stockValue($item->id);

                return $item;
            });

        return view('sfl-inventory::admin.reports.dead-stock', compact('items'));
    }

    public function stockValuation(Request $request): View
    {
        $this->authorize('inv_report.view');

        $items = InvItem::query()
            ->with(['category', 'unit'])
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->active()
            ->orderBy('item_name')
            ->get()
            ->map(function (InvItem $item) {
                $item->current_stock = $this->stock->currentStock($item->id);
                $item->average_rate = $item->current_stock > 0 ? round($this->stock->stockValue($item->id) / $item->current_stock, 2) : 0;
                $item->stock_value = $this->stock->stockValue($item->id);

                return $item;
            })
            ->filter(fn ($item) => $item->current_stock != 0);

        $categories = InvItemCategory::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.stock-valuation', compact('items', 'categories'));
    }

    /**
     * One row per stock movement (GRN in / issue out), with issue quantity
     * split into a column per department and a running balance per item —
     * matches the factory's existing "Store Inventory Report" spreadsheet
     * layout, but computed live from the immutable ledger instead of
     * hand-maintained.
     */
    public function storeInventoryReport(Request $request): View
    {
        $this->authorize('inv_report.view');

        $departments = InvDepartment::active()->orderBy('name')->get();

        $rows = DB::table('inv_stock_transactions as t')
            ->join('inv_items as i', 'i.id', '=', 't.item_id')
            ->join('inv_item_categories as c', 'c.id', '=', 'i.category_id')
            ->join('inv_units as u', 'u.id', '=', 'i.unit_id')
            ->join('inv_stores as s', 's.id', '=', 't.store_id')
            ->leftJoin('inv_departments as d', fn ($join) => $join->on('d.id', '=', 't.department_id')->whereNull('d.deleted_at'))
            ->leftJoin('inv_grns as g', function ($join) {
                $join->on('g.id', '=', 't.reference_id')->where('t.reference_type', '=', 'inv_grn')->whereNull('g.deleted_at');
            })
            ->leftJoin('users as gu', 'gu.id', '=', 'g.received_by')
            ->leftJoin('inv_issues as iss', function ($join) {
                $join->on('iss.id', '=', 't.reference_id')->where('t.reference_type', '=', 'inv_issue')->whereNull('iss.deleted_at');
            })
            ->leftJoin('users as isu', 'isu.id', '=', 'iss.issued_by')
            ->whereNull('i.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereNull('u.deleted_at')
            ->whereNull('s.deleted_at')
            ->when($request->filled('store_id'), fn ($q) => $q->where('t.store_id', $request->store_id))
            ->when($request->filled('item_id'), fn ($q) => $q->where('t.item_id', $request->item_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('t.transaction_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('t.transaction_date', '<=', $request->date_to))
            ->selectRaw('
                t.id, t.item_id, t.store_id, t.transaction_date, t.transaction_type,
                t.qty_in, t.qty_out, t.rate, t.value,
                i.item_code, i.item_name, c.name as category_name, u.short_name as unit,
                s.name as store_name, d.name as department_name,
                g.challan_invoice_no, gu.name as received_by_name,
                iss.issue_no, isu.name as issued_by_name,
                SUM(CASE WHEN t.qty_in > 0 THEN t.qty_in ELSE -t.qty_out END)
                    OVER (PARTITION BY t.item_id, t.store_id ORDER BY t.transaction_date, t.id) as running_balance
            ')
            ->orderBy('i.item_name')
            ->orderBy('t.transaction_date')
            ->orderBy('t.id')
            ->limit(500)
            ->get();

        return view('sfl-inventory::admin.reports.store-inventory-report', compact('rows', 'departments'));
    }

    /**
     * One Excel-download entry point for every report — reuses the exact
     * same method (and therefore the exact same query/filters) that
     * renders the on-screen report, just with printMode=true merged in so
     * the filter form/buttons/pagination are left out of the sheet.
     */
    public function export(Request $request, string $report)
    {
        $method = $this->reportMethodMap()[$report] ?? abort(404);

        // Also flips any print-aware pagination (grn/issue/gate-pass/shipment
        // reports fetch every row instead of one paginated page) since it's
        // the same 'print' flag the underlying report method itself reads.
        // excel_export additionally swaps the base layout: PhpSpreadsheet's
        // HTML reader chokes on a full printMaster2/admin-theme page (fonts,
        // scripts, deep markup) and throws "Failed to load ... as a DOM
        // Document" — export needs the bare table only.
        $request->merge(['print' => 1, 'excel_export' => 1]);

        /** @var View $view */
        $view = $this->{$method}($request);
        $view = $view->with('printMode', true);

        return Excel::download(new InvReportExport($view, $report), $report . '.xlsx');
    }

    private function reportMethodMap(): array
    {
        return [
            'current-stock'          => 'currentStock',
            'stock-summary'          => 'stockSummary',
            'item-history'           => 'itemHistory',
            'store-wise-stock'       => 'storeWiseStock',
            'department-consumption' => 'departmentWiseConsumption',
            'supplier-purchase'      => 'supplierWisePurchase',
            'grn'                    => 'grnReport',
            'grn-item-wise'          => 'grnItemWiseReport',
            'issue'                  => 'issueReport',
            'gate-pass'              => 'gatePassReport',
            'shipment'               => 'shipmentReport',
            'low-stock'              => 'lowStock',
            'dead-stock'             => 'deadStock',
            'stock-valuation'        => 'stockValuation',
            'store-inventory-report' => 'storeInventoryReport',
        ];
    }
}
