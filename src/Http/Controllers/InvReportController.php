<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\SflInventory\Exports\InvReportExport;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Models\InvColor;
use ME\SflInventory\Models\InvDepartment;
use ME\SflInventory\Models\InvGatePass;
use ME\SflInventory\Models\InvGrn;
use ME\SflInventory\Models\InvGrnItem;
use ME\SflInventory\Models\InvIssue;
use ME\SflInventory\Models\InvIssueItem;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvItemCategory;
use ME\SflInventory\Models\InvShipment;
use ME\SflInventory\Models\InvSize;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Models\InvSupplier;
use ME\SflInventory\Models\InvUnit;
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
            ->when($request->filled('item_id'), fn ($q) => $q->where('id', $request->item_id))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('unit_id'), fn ($q) => $q->where('unit_id', $request->unit_id))
            ->active()
            ->orderBy('item_name')
            ->get()
            ->map(function (InvItem $item) {
                $item->current_stock = $this->stock->currentStock($item->id);
                $item->latest_rate = $this->stock->latestRate($item->id);
                $item->stock_value = $this->stock->stockValue($item->id);

                return $item;
            })
            ->filter(fn ($item) => $item->current_stock != 0);

        $filterItems = InvItem::active()->orderBy('item_name')->get();
        $categories = InvItemCategory::active()->orderBy('name')->get();
        $units = InvUnit::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.current-stock', compact('items', 'filterItems', 'categories', 'units'));
    }

    public function stockSummary(Request $request): View
    {
        $this->authorize('inv_report.view');

        $summary = InvItemCategory::active()->orderBy('name')->get()->map(function (InvItemCategory $category) use ($request) {
            $itemIds = InvItem::where('category_id', $category->id)
                ->when($request->filled('item_id'), fn ($q) => $q->whereKey($request->item_id))
                ->pluck('id');
            $qty = 0.0;
            $value = 0.0;
            foreach ($itemIds as $itemId) {
                $qty += $this->stock->currentStock($itemId);
                $value += $this->stock->stockValue($itemId);
            }

            return (object) ['category' => $category, 'items_count' => $itemIds->count(), 'total_qty' => $qty, 'total_value' => $value];
        })->filter(fn ($row) => $row->total_qty != 0);

        $filterItems = InvItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name']);

        return view('sfl-inventory::admin.reports.stock-summary', compact('summary', 'filterItems'));
    }

    public function itemHistory(Request $request): View
    {
        $this->authorize('inv_report.view');

        $items = InvItem::active()->orderBy('item_name')->get();
        $selectedItem = $request->filled('item_id') ? InvItem::find($request->item_id) : null;

        $from = $request->filled('date_from') ? $request->date_from : now()->startOfMonth()->toDateString();
        $to = $request->filled('date_to') ? $request->date_to : now()->toDateString();

        // The running balance ("row-wise current stock") must be computed
        // over every transaction ever posted for this item/store — not just
        // the ones inside the selected date range — otherwise a row's
        // balance would only reflect movements since the range start, not
        // the item's real stock at that point in time. So the window
        // function runs unfiltered in this inner query, and the date-range
        // and reversal-row filters are applied only in the outer query.
        // Partitioned by color_id/size_id too (in addition to item/store) so
        // the running balance is correct per variant once an item has more
        // than one — otherwise two different colors of the same generic
        // item would wrongly share one running total.
        $withBalance = DB::table('inv_stock_transactions as t')
            ->join('inv_items as i', 'i.id', '=', 't.item_id')
            ->join('inv_stores as s', 's.id', '=', 't.store_id')
            ->leftJoin('inv_colors as c', 'c.id', '=', 't.color_id')
            ->leftJoin('inv_sizes as sz', 'sz.id', '=', 't.size_id')
            ->whereNull('i.deleted_at')
            ->whereNull('s.deleted_at')
            ->when($selectedItem, fn ($q) => $q->where('t.item_id', $selectedItem->id))
            ->when($request->filled('color_id'), fn ($q) => $q->where('t.color_id', $request->color_id))
            ->when($request->filled('size_id'), fn ($q) => $q->where('t.size_id', $request->size_id))
            ->selectRaw('
                t.id, t.item_id, t.store_id, t.color_id, t.size_id, t.transaction_date, t.transaction_type,
                t.qty_in, t.qty_out, t.rate, t.value,
                i.item_code, i.item_name, s.name as store_name, c.name as color_name, sz.name as size_name,
                SUM(CASE WHEN t.qty_in > 0 THEN t.qty_in ELSE -t.qty_out END)
                    OVER (PARTITION BY t.item_id, t.store_id, t.color_id, t.size_id ORDER BY t.transaction_date, t.id) as running_balance
            ');

        $filtered = DB::query()->fromSub($withBalance, 'x')
            ->whereDate('x.transaction_date', '>=', $from)
            ->whereDate('x.transaction_date', '<=', $to)
            ->where('x.transaction_type', 'not like', '%\_reversal');

        // Footer totals cover every filtered row, not just the current page.
        $totals = (clone $filtered)->selectRaw('COALESCE(SUM(x.qty_in), 0) as qty_in, COALESCE(SUM(x.qty_out), 0) as qty_out, COALESCE(SUM(CASE WHEN x.qty_in > 0 THEN x.value ELSE -x.value END), 0) as value')->first();

        $transactions = $filtered
            ->orderBy('x.transaction_date')
            ->orderBy('x.id')
            ->paginate($request->boolean('print') ? 100000 : 50)
            ->withQueryString();

        $currentStock = $selectedItem ? $this->stock->currentStock($selectedItem->id) : null;
        $colors = InvColor::active()->orderBy('name')->get();
        $sizes = InvSize::active()->ordered()->get();

        return view('sfl-inventory::admin.reports.item-history', compact('items', 'transactions', 'totals', 'selectedItem', 'from', 'to', 'currentStock', 'colors', 'sizes'));
    }

    /**
     * One row per document that ever touched this item — Purchase Order,
     * GRN, Requisition, Issue — each carrying who did it and the document
     * number, so "kobe kon purchase e ke entry disey, ke receive korese"
     * etc. can be answered from a single screen instead of hunting through
     * four separate list pages.
     */
    public function itemFullTrail(Request $request): View
    {
        $this->authorize('inv_report.view');

        $items = InvItem::active()->orderBy('item_name')->get();
        $selectedItem = $request->filled('item_id') ? InvItem::find($request->item_id) : null;

        $rows = collect();

        if ($selectedItem) {
            $purchaseOrders = DB::table('inv_purchase_order_items as poi')
                ->join('inv_purchase_orders as po', 'po.id', '=', 'poi.purchase_order_id')
                ->leftJoin('users as u', 'u.id', '=', 'po.created_by')
                ->leftJoin('inv_suppliers as sup', 'sup.id', '=', 'po.supplier_id')
                ->leftJoin('inv_colors as c', 'c.id', '=', 'poi.color_id')
                ->leftJoin('inv_sizes as sz', 'sz.id', '=', 'poi.size_id')
                ->where('poi.item_id', $selectedItem->id)
                ->whereNull('po.deleted_at')
                ->selectRaw("'Purchase Order' as document_type, po.po_number as document_no, po.order_date as txn_date, poi.quantity as qty, c.name as color_name, sz.name as size_name, u.name as person_name, sup.name as party_name, po.status as status, NULL as stock_effect, 1 as sort_group, 0 as ledger_id");

            $requisitions = DB::table('inv_requisition_items as ri')
                ->join('inv_requisitions as r', 'r.id', '=', 'ri.requisition_id')
                ->leftJoin('users as u', 'u.id', '=', 'r.requested_by')
                ->leftJoin('inv_departments as d', 'd.id', '=', 'r.department_id')
                ->leftJoin('inv_colors as c', 'c.id', '=', 'ri.color_id')
                ->leftJoin('inv_sizes as sz', 'sz.id', '=', 'ri.size_id')
                ->where('ri.item_id', $selectedItem->id)
                ->whereNull('r.deleted_at')
                ->selectRaw("'Requisition' as document_type, r.requisition_no as document_no, r.requisition_date as txn_date, ri.requested_qty as qty, c.name as color_name, sz.name as size_name, u.name as person_name, d.name as party_name, r.status as status, NULL as stock_effect, 2 as sort_group, 0 as ledger_id");

            // Every stock movement straight from the ledger (GRN, Issue, their
            // reversals, adjustments, opening, …) — one row per ledger entry,
            // signed, so the running Current Qty always reconciles with the
            // item's real current stock.
            $movements = DB::table('inv_stock_transactions as t')
                ->leftJoin('inv_grns as g', fn ($j) => $j->on('g.id', '=', 't.reference_id')->where('t.reference_type', '=', 'inv_grn'))
                ->leftJoin('inv_issues as iss', fn ($j) => $j->on('iss.id', '=', 't.reference_id')->where('t.reference_type', '=', 'inv_issue'))
                ->leftJoin('inv_stock_adjustments as adj', fn ($j) => $j->on('adj.id', '=', 't.reference_id')->where('t.reference_type', '=', 'inv_stock_adjustment'))
                ->leftJoin('hr_employees as gre', 'gre.id', '=', 'g.received_by')
                ->leftJoin('users as u', 'u.id', '=', DB::raw('COALESCE(iss.issued_by, g.created_by, t.created_by)'))
                ->leftJoin('inv_departments as d', 'd.id', '=', 'iss.department_id')
                ->leftJoin('inv_stores as s', 's.id', '=', 't.store_id')
                ->leftJoin('inv_colors as c', 'c.id', '=', 't.color_id')
                ->leftJoin('inv_sizes as sz', 'sz.id', '=', 't.size_id')
                ->where('t.item_id', $selectedItem->id)
                ->selectRaw("
                    CASE t.transaction_type
                        WHEN 'grn' THEN 'GRN' WHEN 'grn_reversal' THEN 'GRN Reversal'
                        WHEN 'issue' THEN 'Issue' WHEN 'issue_reversal' THEN 'Issue Reversal'
                        WHEN 'adjustment' THEN 'Adjustment' WHEN 'adjustment_reversal' THEN 'Adjustment Reversal'
                        WHEN 'opening' THEN 'Opening' ELSE REPLACE(t.transaction_type, '_', ' ') END as document_type,
                    COALESCE(g.grn_number, iss.issue_no, adj.adjustment_no, t.remarks) as document_no,
                    t.transaction_date as txn_date,
                    t.qty_in + t.qty_out as qty,
                    c.name as color_name, sz.name as size_name,
                    COALESCE(gre.name, u.name) as person_name,
                    CONCAT(s.name, COALESCE(CONCAT(' → ', d.name), '')) as party_name,
                    COALESCE(g.status, iss.status, adj.status) as status,
                    t.qty_in - t.qty_out as stock_effect, 3 as sort_group, t.id as ledger_id
                ");

            $rows = $purchaseOrders
                ->unionAll($requisitions)
                ->unionAll($movements)
                ->orderBy('txn_date')
                ->orderBy('sort_group')
                ->orderBy('ledger_id')
                ->get();

            // Running stock after each row — PO / Requisition don't move stock.
            $running = 0.0;
            foreach ($rows as $row) {
                if ($row->stock_effect !== null) {
                    $running += (float) $row->stock_effect;
                }
                $row->current_qty = $running;
            }
        }

        // Current Stock / Stock Value, per store the item has movement in —
        // same figures as Main Store Inventory (value = qty × latest rate).
        $stockByStore = collect();
        if ($selectedItem) {
            $selectedItem->loadMissing('unit');
            $stockByStore = InvStore::whereIn('id', DB::table('inv_stock_transactions')->where('item_id', $selectedItem->id)->distinct()->pluck('store_id'))
                ->orderBy('name')->get()
                ->map(fn (InvStore $store) => (object) [
                    'store' => $store->name,
                    'qty'   => $this->stock->currentStock($selectedItem->id, $store->id),
                    'rate'  => $this->stock->latestRate($selectedItem->id, $store->id),
                    'value' => $this->stock->stockValue($selectedItem->id, $store->id),
                ]);
        }

        return view('sfl-inventory::admin.reports.item-full-trail', compact('items', 'selectedItem', 'rows', 'stockByStore'));
    }

    public function storeWiseStock(Request $request): View
    {
        $this->authorize('inv_report.view');

        $stores = InvStore::active()->orderBy('name')->get()->map(function (InvStore $store) use ($request) {
            $itemIds = DB::table('inv_stock_transactions')->where('store_id', $store->id)
                ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->item_id))
                ->distinct()->pluck('item_id');
            $value = 0.0;
            $qty = 0.0;
            foreach ($itemIds as $itemId) {
                $value += $this->stock->stockValue($itemId, $store->id);
                $qty += $this->stock->currentStock($itemId, $store->id);
            }

            return (object) ['store' => $store, 'items_count' => $itemIds->count(), 'total_qty' => $qty, 'total_value' => $value];
        });

        $filterItems = InvItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name']);

        return view('sfl-inventory::admin.reports.store-wise-stock', compact('stores', 'filterItems'));
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
            ->when($request->filled('item_id'), fn ($q) => $q->where('pci.item_id', $request->item_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('pc.consumption_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('pc.consumption_date', '<=', $request->date_to))
            ->groupBy('d.id', 'd.name')
            ->select('d.name as department_name', DB::raw('SUM(pci.consumed_qty) as total_consumed'), DB::raw('SUM(pci.waste_qty) as total_waste'))
            ->orderBy('d.name')
            ->get();

        $departments = InvDepartment::active()->orderBy('name')->get();
        $items = InvItem::active()->orderBy('item_name')->get();

        return view('sfl-inventory::admin.reports.department-consumption', compact('rows', 'departments', 'items'));
    }

    public function supplierWisePurchase(Request $request): View
    {
        $this->authorize('inv_report.view');

        $rows = DB::table('inv_grn_items as gi')
            ->join('inv_grns as g', 'g.id', '=', 'gi.grn_id')
            ->join('inv_suppliers as s', 's.id', '=', 'g.supplier_id')
            ->whereNull('g.deleted_at')
            ->whereNull('s.deleted_at')
            ->where('g.status', 'posted')
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('g.supplier_id', $request->supplier_id))
            ->when($request->filled('item_id'), fn ($q) => $q->where('gi.item_id', $request->item_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('g.receive_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('g.receive_date', '<=', $request->date_to))
            ->groupBy('s.id', 's.name')
            ->select('s.name as supplier_name', DB::raw('SUM(gi.received_qty) as total_qty'), DB::raw('SUM(gi.amount) as total_amount'))
            ->orderByDesc('total_amount')
            ->get();

        $suppliers = InvSupplier::active()->orderBy('name')->get();
        $items = InvItem::active()->orderBy('item_name')->get();

        return view('sfl-inventory::admin.reports.supplier-purchase', compact('rows', 'suppliers', 'items'));
    }

    public function supplierList(Request $request): View
    {
        $this->authorize('inv_report.view');

        $status = $request->input('status');

        $suppliers = InvSupplier::query()
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('code', 'like', '%' . $request->search . '%')))
            ->when($status, fn ($q) => $q->where('is_active', $status === 'active'))
            ->when($request->filled('item_id'), fn ($q) => $q->whereIn('id', DB::table('inv_grns as g')
                ->join('inv_grn_items as gi', 'gi.grn_id', '=', 'g.id')
                ->whereNull('g.deleted_at')->where('gi.item_id', $request->item_id)->whereNotNull('g.supplier_id')
                ->select('g.supplier_id')))
            ->orderBy('name')
            ->get();

        $dataRangeLabel = match (true) {
            $request->filled('search') => 'Search: ' . $request->search,
            $status === 'active'       => 'Active Suppliers',
            $status === 'inactive'     => 'Inactive Suppliers',
            default                    => 'All Supplier',
        };

        $filterItems = InvItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name']);

        return view('sfl-inventory::admin.reports.supplier-list', compact('suppliers', 'dataRangeLabel', 'filterItems'));
    }

    /**
     * Merged Buyer + Style report — one grouped table, sorted Style ->
     * Buyer -> Item, with the Style/Buyer cells rowspan-merged across
     * consecutive rows that share the same value (see rowSpans() below).
     * One row per received "lot" (a GRN line — keeps Lot/Batch/Expiry
     * traceability), plus Issue/Delivery Qty and Balance columns computed
     * per (buyer, style, item) across all receipts and *approved* issues
     * for that exact combination (a pending/authorized-but-not-yet-approved
     * challan is only a claim — stock hasn't actually left yet, see
     * InvIssueController::postIssueStock() — so it's excluded here or
     * Balance would look like real stock had gone negative when nothing
     * has physically moved). Deliberately NOT scoped to date_from/date_to,
     * so Balance always reflects true remaining stock, never a misleading
     * partial-period number just because the lot list is date-filtered.
     * Filtering by style alone, buyer alone, item alone, or any
     * combination all narrow the same single query.
     */
    public function buyerStyleWiseReport(Request $request): View
    {
        $this->authorize('inv_report.view');

        $lines = InvGrnItem::query()
            ->select('inv_grn_items.*')
            ->join('inv_grns', 'inv_grns.id', '=', 'inv_grn_items.grn_id')
            ->whereNull('inv_grns.deleted_at')
            ->where($this->buyerStyleContextClause('inv_grns'))
            ->with(['item.unit', 'color', 'size', 'grn.buyer'])
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('inv_grns.buyer_id', $request->buyer_id))
            ->when($request->filled('style'), fn ($q) => $q->where('inv_grns.style', $request->style))
            ->when($request->filled('item_id'), fn ($q) => $q->where('inv_grn_items.item_id', $request->item_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('inv_grns.receive_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('inv_grns.receive_date', '<=', $request->date_to))
            ->orderByRaw("COALESCE(inv_grns.style, '')")
            ->orderBy('inv_grns.buyer_id')
            ->orderBy('inv_grn_items.item_id')
            ->orderBy('inv_grns.receive_date')
            ->orderBy('inv_grn_items.id')
            ->get();

        $receivedTotals = DB::table('inv_grn_items as gi')
            ->join('inv_grns as g', 'g.id', '=', 'gi.grn_id')
            ->whereNull('g.deleted_at')
            ->where($this->buyerStyleContextClause('g'))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('g.buyer_id', $request->buyer_id))
            ->when($request->filled('style'), fn ($q) => $q->where('g.style', $request->style))
            ->when($request->filled('item_id'), fn ($q) => $q->where('gi.item_id', $request->item_id))
            ->groupBy('g.buyer_id', 'g.style', 'gi.item_id')
            ->select('g.buyer_id', DB::raw("COALESCE(g.style, '') as style"), 'gi.item_id', DB::raw('SUM(gi.received_qty) as total_qty'))
            ->get()
            ->keyBy(fn ($row) => $row->buyer_id . '|' . $row->style . '|' . $row->item_id);

        // Only 'approved' issues count — that's the moment stock actually
        // leaves the store (see InvIssueController::postIssueStock()). A
        // still-pending/authorized challan is just a claim, not a real
        // movement yet, so counting it here would make Balance look like
        // real stock had gone negative when physically nothing has moved.
        $issuedTotals = DB::table('inv_issue_items as ii')
            ->join('inv_issues as i', 'i.id', '=', 'ii.issue_id')
            ->whereNull('i.deleted_at')
            ->where('i.status', 'approved')
            ->where($this->buyerStyleContextClause('i'))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('i.buyer_id', $request->buyer_id))
            ->when($request->filled('style'), fn ($q) => $q->where('i.style', $request->style))
            ->when($request->filled('item_id'), fn ($q) => $q->where('ii.item_id', $request->item_id))
            ->groupBy('i.buyer_id', 'i.style', 'ii.item_id')
            ->select('i.buyer_id', DB::raw("COALESCE(i.style, '') as style"), 'ii.item_id', DB::raw('SUM(ii.issued_qty) as total_qty'))
            ->get()
            ->keyBy(fn ($row) => $row->buyer_id . '|' . $row->style . '|' . $row->item_id);

        $rows = $lines->values();
        foreach ($rows as $line) {
            $key = ($line->grn->buyer_id ?? '') . '|' . ($line->grn->style ?? '') . '|' . $line->item_id;
            $line->item_total_received = (float) ($receivedTotals[$key]->total_qty ?? 0);
            $line->item_total_issued = (float) ($issuedTotals[$key]->total_qty ?? 0);
            $line->item_balance = $line->item_total_received - $line->item_total_issued;
        }

        $styleSpans = $this->rowSpans($rows, fn ($line) => $line->grn->style ?? '');
        $buyerSpans = $this->rowSpans($rows, fn ($line) => $line->grn->buyer_id ?? 0);

        $buyers = InvBuyer::active()->orderBy('name')->get();
        $items = InvItem::active()->orderBy('item_name')->get();
        $styles = $this->styleOptions();

        return view('sfl-inventory::admin.reports.buyer-style-wise', compact('rows', 'styleSpans', 'buyerSpans', 'buyers', 'items', 'styles'));
    }

    /** Every style ever received or issued — for the Style dropdown filters. */
    private function styleOptions(): \Illuminate\Support\Collection
    {
        return DB::table('inv_grns')->whereNull('deleted_at')->whereNotNull('style')->where('style', '!=', '')->distinct()->pluck('style')
            ->merge(DB::table('inv_issues')->whereNull('deleted_at')->whereNotNull('style')->where('style', '!=', '')->distinct()->pluck('style'))
            ->map(fn ($s) => trim($s))->unique()->sort(SORT_NATURAL)->values();
    }

    /**
     * A row belongs to this report if its document carries a buyer, a
     * style, or both — matches InvGrn/InvIssue's shared buyer_id/style
     * columns (see the migration comment on
     * add_buyer_context_to_inv_grns_table).
     */
    private function buyerStyleContextClause(string $table): \Closure
    {
        return function ($q) use ($table) {
            $q->whereNotNull("{$table}.buyer_id")
                ->orWhere(function ($q2) use ($table) {
                    $q2->whereNotNull("{$table}.style")->where("{$table}.style", '!=', '');
                });
        };
    }

    /**
     * For a presorted list, returns [index => rowspan]: the first row of a
     * run of consecutive items sharing the same key gets the full span,
     * every row after it gets 0 (render nothing there — it's covered by
     * the rowspan above). Powers the Style/Buyer cell merging.
     */
    private function rowSpans($rows, callable $keyFn): array
    {
        $rows = $rows->values();
        $count = $rows->count();
        $spans = [];
        $i = 0;
        while ($i < $count) {
            $key = $keyFn($rows[$i]);
            $j = $i + 1;
            while ($j < $count && $keyFn($rows[$j]) === $key) {
                $j++;
            }
            $spans[$i] = $j - $i;
            for ($k = $i + 1; $k < $j; $k++) {
                $spans[$k] = 0;
            }
            $i = $j;
        }

        return $spans;
    }

    public function grnReport(Request $request): View
    {
        $this->authorize('inv_report.view');

        $query = InvGrn::query()
            ->with(['store', 'supplier', 'purchaseOrder'])
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('item_id'), fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('item_id', $request->item_id)))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('receive_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('receive_date', '<=', $request->date_to));

        $grandTotal = (float) (clone $query)->sum('total_amount');

        $grns = $query->latest('receive_date')
            ->paginate($request->boolean('print') ? 100000 : 30)
            ->withQueryString();

        $stores = InvStore::active()->orderBy('name')->get();
        $suppliers = InvSupplier::active()->orderBy('name')->get();
        $items = InvItem::active()->orderBy('item_name')->get();

        return view('sfl-inventory::admin.reports.grn-report', compact('grns', 'stores', 'suppliers', 'items', 'grandTotal'));
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
            ->with(['item.unit', 'color', 'size', 'grn.store', 'grn.supplier', 'grn.buyer', 'grn.purchaseOrder', 'grn.creator'])
            ->when($request->filled('item_id'), fn ($q) => $q->where('inv_grn_items.item_id', $request->item_id))
            ->when($request->filled('color_id'), fn ($q) => $q->where('inv_grn_items.color_id', $request->color_id))
            ->when($request->filled('size_id'), fn ($q) => $q->where('inv_grn_items.size_id', $request->size_id))
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
        $colors = InvColor::active()->orderBy('name')->get();
        $sizes = InvSize::active()->ordered()->get();

        return view('sfl-inventory::admin.reports.grn-item-wise-report', compact('lines', 'items', 'stores', 'suppliers', 'colors', 'sizes'));
    }

    /**
     * One row per received line that was given an expiry date at GRN time
     * (src/database/migrations/2026_08_18_000002_...). Status is derived
     * live from today's date against that date — nothing is cached, same
     * "never store what can be computed" rule the rest of this package
     * follows for stock.
     */
    public function expiryTracking(Request $request): View
    {
        $this->authorize('inv_report.view');

        $withinDays = $request->filled('within_days') ? (int) $request->within_days : 30;

        $linesQuery = InvGrnItem::query()
            ->select('inv_grn_items.*')
            ->join('inv_grns', 'inv_grns.id', '=', 'inv_grn_items.grn_id')
            ->whereNull('inv_grns.deleted_at')
            ->whereNotNull('inv_grn_items.expiry_date')
            ->with(['item.unit', 'grn.store'])
            ->when($request->filled('item_id'), fn ($q) => $q->where('inv_grn_items.item_id', $request->item_id))
            ->when($request->filled('store_id'), fn ($q) => $q->where('inv_grns.store_id', $request->store_id))
            ->when($request->filled('status'), function ($q) use ($request, $withinDays) {
                $today = now()->toDateString();
                $horizon = now()->addDays($withinDays)->toDateString();
                if ($request->status === 'expired') {
                    $q->whereDate('inv_grn_items.expiry_date', '<', $today);
                } elseif ($request->status === 'expiring_soon') {
                    $q->whereDate('inv_grn_items.expiry_date', '>=', $today)
                        ->whereDate('inv_grn_items.expiry_date', '<=', $horizon);
                } elseif ($request->status === 'ok') {
                    $q->whereDate('inv_grn_items.expiry_date', '>', $horizon);
                }
            });

        $grandQty = (float) (clone $linesQuery)->sum('inv_grn_items.received_qty');

        $lines = $linesQuery
            ->orderBy('inv_grn_items.expiry_date')
            ->paginate($request->boolean('print') ? 100000 : 30)
            ->withQueryString();

        $items = InvItem::active()->orderBy('item_name')->get();
        $stores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.expiry-tracking', compact('lines', 'items', 'stores', 'withinDays', 'grandQty'));
    }

    public function issueReport(Request $request): View
    {
        $this->authorize('inv_report.view');

        $issues = InvIssue::query()
            ->with(['store', 'department', 'buyer'])
            // Issue Qty = what actually left the store (issued_qty); Delivery
            // Qty = what the receiving department has confirmed getting
            // (department_received_qty) — these can differ until the
            // department confirms receipt (see InvIssueController::receive()).
            ->withSum('items as issue_qty_total', 'issued_qty')
            ->withSum('items as delivery_qty_total', 'department_received_qty')
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('style'), fn ($q) => $q->where('style', $request->style))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('item_id'), fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('item_id', $request->item_id)))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('issue_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('issue_date', '<=', $request->date_to))
            ->latest('issue_date')
            ->paginate($request->boolean('print') ? 100000 : 30)
            ->withQueryString();

        $departments = InvDepartment::active()->orderBy('name')->get();
        $buyers = InvBuyer::active()->orderBy('name')->get();
        $items = InvItem::active()->orderBy('item_name')->get();
        $stores = InvStore::active()->orderBy('name')->get();
        $styles = $this->styleOptions();

        return view('sfl-inventory::admin.reports.issue-report', compact('issues', 'departments', 'buyers', 'items', 'stores', 'styles'));
    }

    public function gatePassReport(Request $request): View
    {
        $this->authorize('inv_report.view');

        $gatePassesQuery = InvGatePass::query()
            ->with(['buyer', 'store'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('gate_pass_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('gate_pass_date', '<=', $request->date_to))
            ->when($request->filled('item_id'), fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('item_id', $request->item_id)));

        $grandQty = (float) DB::table('inv_gate_pass_items')
            ->whereIn('gate_pass_id', (clone $gatePassesQuery)->select('id'))
            ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->item_id))
            ->sum('quantity');

        $gatePasses = $gatePassesQuery
            ->latest('gate_pass_date')
            ->paginate($request->boolean('print') ? 100000 : 30)
            ->withQueryString();

        $filterItems = InvItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name']);

        return view('sfl-inventory::admin.reports.gate-pass-report', compact('gatePasses', 'grandQty', 'filterItems'));
    }

    public function shipmentReport(Request $request): View
    {
        $this->authorize('inv_report.view');

        $shipmentsQuery = InvShipment::query()
            ->with(['buyer', 'gatePass', 'gatePasses'])
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('shipment_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('shipment_date', '<=', $request->date_to))
            ->when($request->filled('item_id'), fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('item_id', $request->item_id)));

        $grandQty = (float) DB::table('inv_shipment_items')
            ->whereIn('shipment_id', (clone $shipmentsQuery)->select('id'))
            ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->item_id))
            ->sum('quantity');

        $shipments = $shipmentsQuery
            ->latest('shipment_date')
            ->paginate($request->boolean('print') ? 100000 : 30)
            ->withQueryString();

        $buyers = InvBuyer::active()->orderBy('name')->get();

        $filterItems = InvItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name']);

        return view('sfl-inventory::admin.reports.shipment-report', compact('shipments', 'buyers', 'grandQty', 'filterItems'));
    }

    public function lowStock(Request $request): View
    {
        $this->authorize('inv_report.view');

        $items = InvItem::active()->with(['category', 'unit'])
            ->when($request->filled('item_id'), fn ($q) => $q->whereKey($request->item_id))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->orderBy('item_name')->get()
            ->filter(fn (InvItem $item) => $this->stock->isLowStock($item))
            ->map(function (InvItem $item) {
                $item->current_stock = $this->stock->currentStock($item->id);

                return $item;
            });

        $filterItems = InvItem::active()->orderBy('item_name')->get();
        $categories = InvItemCategory::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.low-stock', compact('items', 'filterItems', 'categories'));
    }

    public function deadStock(Request $request): View
    {
        $this->authorize('inv_report.view');

        $items = InvItem::active()->with(['category', 'unit'])
            ->when($request->filled('item_id'), fn ($q) => $q->whereKey($request->item_id))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->orderBy('item_name')->get()
            ->filter(fn (InvItem $item) => $this->stock->isDeadStock($item))
            ->map(function (InvItem $item) {
                $item->current_stock = $this->stock->currentStock($item->id);
                $item->stock_value = $this->stock->stockValue($item->id);

                return $item;
            });

        $filterItems = InvItem::active()->orderBy('item_name')->get();
        $categories = InvItemCategory::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.dead-stock', compact('items', 'filterItems', 'categories'));
    }

    public function stockValuation(Request $request): View
    {
        $this->authorize('inv_report.view');

        $items = InvItem::query()
            ->with(['category', 'unit'])
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('item_id'), fn ($q) => $q->whereKey($request->item_id))
            ->active()
            ->orderBy('item_name')
            ->get()
            ->map(function (InvItem $item) {
                $item->current_stock = $this->stock->currentStock($item->id);
                $item->latest_rate = $this->stock->latestRate($item->id);
                $item->stock_value = $this->stock->stockValue($item->id);

                return $item;
            })
            ->filter(fn ($item) => $item->current_stock != 0);

        $categories = InvItemCategory::active()->orderBy('name')->get();
        $filterItems = InvItem::active()->orderBy('item_name')->get();

        return view('sfl-inventory::admin.reports.stock-valuation', compact('items', 'categories', 'filterItems'));
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

        // The running balance must be computed over every transaction
        // (including *_reversal entries posted when a GRN/adjustment/etc. is
        // edited or deleted) so it stays numerically correct — but reversal
        // rows themselves are just internal corrections for a user's own
        // mistake, not something they need to see line-by-line. So the
        // window function runs unfiltered in this inner query, and the
        // reversal rows are dropped only in the outer query, after the
        // balance for every later row already accounts for them.
        $withBalance = DB::table('inv_stock_transactions as t')
            ->join('inv_items as i', 'i.id', '=', 't.item_id')
            ->join('inv_item_categories as c', 'c.id', '=', 'i.category_id')
            ->join('inv_units as u', 'u.id', '=', 'i.unit_id')
            ->join('inv_stores as s', 's.id', '=', 't.store_id')
            ->leftJoin('inv_departments as d', fn ($join) => $join->on('d.id', '=', 't.department_id')->whereNull('d.deleted_at'))
            ->leftJoin('inv_grns as g', function ($join) {
                $join->on('g.id', '=', 't.reference_id')->where('t.reference_type', '=', 'inv_grn')->whereNull('g.deleted_at');
            })
            ->leftJoin('hr_employees as gu', 'gu.id', '=', 'g.received_by')
            ->leftJoin('inv_issues as iss', function ($join) {
                $join->on('iss.id', '=', 't.reference_id')->where('t.reference_type', '=', 'inv_issue')->whereNull('iss.deleted_at');
            })
            ->leftJoin('users as isu', 'isu.id', '=', 'iss.issued_by')
            ->whereNull('i.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereNull('u.deleted_at')
            ->whereNull('s.deleted_at')
            // Store/item narrow whole partitions, so they're safe here; the
            // date range is applied only in the outer query — filtering it
            // here would restart every running balance at 0 on date_from.
            ->when($request->filled('store_id'), fn ($q) => $q->where('t.store_id', $request->store_id))
            ->when($request->filled('item_id'), fn ($q) => $q->where('t.item_id', $request->item_id))
            ->selectRaw('
                t.id, t.item_id, t.store_id, t.transaction_date, t.transaction_type,
                t.qty_in, t.qty_out, t.rate, t.value,
                i.item_code, i.item_name, c.name as category_name, u.short_name as unit,
                s.name as store_name, d.name as department_name,
                g.challan_invoice_no, gu.name as received_by_name,
                iss.issue_no, isu.name as issued_by_name,
                SUM(CASE WHEN t.qty_in > 0 THEN t.qty_in ELSE -t.qty_out END)
                    OVER (PARTITION BY t.item_id, t.store_id, t.color_id, t.size_id ORDER BY t.transaction_date, t.id) as running_balance
            ');

        $filtered = DB::query()->fromSub($withBalance, 'x')
            ->where('x.transaction_type', 'not like', '%\_reversal')
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('x.transaction_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('x.transaction_date', '<=', $request->date_to));

        // Footer totals cover every filtered row, not just the current page.
        $totals = (clone $filtered)->selectRaw("
            COALESCE(SUM(x.qty_in), 0) as qty_in,
            COALESCE(SUM(CASE WHEN x.transaction_type = 'issue' AND x.qty_out > 0 AND x.department_name IS NOT NULL THEN 0 ELSE x.qty_out END), 0) as other_out,
            COALESCE(SUM(CASE WHEN x.qty_in > 0 THEN x.value ELSE -x.value END), 0) as value
        ")->first();
        $departmentTotals = (clone $filtered)->where('x.transaction_type', 'issue')->whereNotNull('x.department_name')
            ->groupBy('x.department_name')->selectRaw('x.department_name, SUM(x.qty_out) as qty')->pluck('qty', 'department_name');

        $rows = $filtered
            ->orderBy('x.item_name')
            ->orderBy('x.transaction_date')
            ->orderBy('x.id')
            ->paginate($request->boolean('print') ? 100000 : 100)
            ->withQueryString();

        $items = InvItem::active()->orderBy('item_name')->get();
        $stores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.store-inventory-report', compact('rows', 'departments', 'totals', 'departmentTotals', 'items', 'stores'));
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
            'item-full-trail'        => 'itemFullTrail',
            'store-wise-stock'       => 'storeWiseStock',
            'department-consumption' => 'departmentWiseConsumption',
            'supplier-purchase'      => 'supplierWisePurchase',
            'supplier-list'          => 'supplierList',
            'buyer-style-wise'       => 'buyerStyleWiseReport',
            'grn'                    => 'grnReport',
            'grn-item-wise'          => 'grnItemWiseReport',
            'expiry-tracking'        => 'expiryTracking',
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
