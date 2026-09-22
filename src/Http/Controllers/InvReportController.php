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
            ->when($request->filled('item_code'), fn ($q) => $q->where('item_code', 'like', '%' . $request->item_code . '%'))
            ->when($request->filled('item_name'), fn ($q) => $q->where('item_name', 'like', '%' . $request->item_name . '%'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('unit_id'), fn ($q) => $q->where('unit_id', $request->unit_id))
            ->when($request->filled('item_id'), fn ($q) => $q->where('id', $request->item_id))
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
        $units = InvUnit::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.current-stock', compact('items', 'categories', 'units'));
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

        $transactions = DB::query()->fromSub($withBalance, 'x')
            ->whereDate('x.transaction_date', '>=', $from)
            ->whereDate('x.transaction_date', '<=', $to)
            ->where('x.transaction_type', 'not like', '%\_reversal')
            ->orderBy('x.transaction_date')
            ->orderBy('x.id')
            ->paginate($request->boolean('print') ? 100000 : 50)
            ->withQueryString();

        $currentStock = $selectedItem ? $this->stock->currentStock($selectedItem->id) : null;
        $colors = InvColor::active()->orderBy('name')->get();
        $sizes = InvSize::active()->ordered()->get();

        return view('sfl-inventory::admin.reports.item-history', compact('items', 'transactions', 'selectedItem', 'from', 'to', 'currentStock', 'colors', 'sizes'));
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
                ->selectRaw("'Purchase Order' as document_type, po.po_number as document_no, po.order_date as txn_date, poi.quantity as qty, c.name as color_name, sz.name as size_name, u.name as person_name, sup.name as party_name, po.status as status");

            $grns = DB::table('inv_grn_items as gi')
                ->join('inv_grns as g', 'g.id', '=', 'gi.grn_id')
                ->leftJoin('users as u', 'u.id', '=', 'g.created_by')
                ->leftJoin('hr_employees as emp', 'emp.id', '=', 'g.received_by')
                ->leftJoin('inv_stores as s', 's.id', '=', 'g.store_id')
                ->leftJoin('inv_colors as c', 'c.id', '=', 'gi.color_id')
                ->leftJoin('inv_sizes as sz', 'sz.id', '=', 'gi.size_id')
                ->where('gi.item_id', $selectedItem->id)
                ->whereNull('g.deleted_at')
                ->selectRaw("'GRN' as document_type, g.grn_number as document_no, g.receive_date as txn_date, gi.received_qty as qty, c.name as color_name, sz.name as size_name, COALESCE(emp.name, u.name) as person_name, s.name as party_name, g.status as status");

            $requisitions = DB::table('inv_requisition_items as ri')
                ->join('inv_requisitions as r', 'r.id', '=', 'ri.requisition_id')
                ->leftJoin('users as u', 'u.id', '=', 'r.requested_by')
                ->leftJoin('inv_departments as d', 'd.id', '=', 'r.department_id')
                ->leftJoin('inv_colors as c', 'c.id', '=', 'ri.color_id')
                ->leftJoin('inv_sizes as sz', 'sz.id', '=', 'ri.size_id')
                ->where('ri.item_id', $selectedItem->id)
                ->whereNull('r.deleted_at')
                ->selectRaw("'Requisition' as document_type, r.requisition_no as document_no, r.requisition_date as txn_date, ri.requested_qty as qty, c.name as color_name, sz.name as size_name, u.name as person_name, d.name as party_name, r.status as status");

            $issues = DB::table('inv_issue_items as ii')
                ->join('inv_issues as i', 'i.id', '=', 'ii.issue_id')
                ->leftJoin('users as u', 'u.id', '=', 'i.issued_by')
                ->leftJoin('inv_departments as d', 'd.id', '=', 'i.department_id')
                ->leftJoin('inv_colors as c', 'c.id', '=', 'ii.color_id')
                ->leftJoin('inv_sizes as sz', 'sz.id', '=', 'ii.size_id')
                ->where('ii.item_id', $selectedItem->id)
                ->whereNull('i.deleted_at')
                ->selectRaw("'Issue' as document_type, i.issue_no as document_no, i.issue_date as txn_date, ii.issued_qty as qty, c.name as color_name, sz.name as size_name, u.name as person_name, d.name as party_name, i.status as status");

            $rows = $purchaseOrders
                ->unionAll($grns)
                ->unionAll($requisitions)
                ->unionAll($issues)
                ->orderBy('txn_date')
                ->get();
        }

        return view('sfl-inventory::admin.reports.item-full-trail', compact('items', 'selectedItem', 'rows'));
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

    public function supplierList(Request $request): View
    {
        $this->authorize('inv_report.view');

        $status = $request->input('status');

        $suppliers = InvSupplier::query()
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('code', 'like', '%' . $request->search . '%')))
            ->when($status, fn ($q) => $q->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->get();

        $dataRangeLabel = match (true) {
            $request->filled('search') => 'Search: ' . $request->search,
            $status === 'active'       => 'Active Suppliers',
            $status === 'inactive'     => 'Inactive Suppliers',
            default                    => 'All Supplier',
        };

        return view('sfl-inventory::admin.reports.supplier-list', compact('suppliers', 'dataRangeLabel'));
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
            ->when($request->filled('style'), fn ($q) => $q->where('inv_grns.style', 'like', '%' . $request->style . '%'))
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
            ->when($request->filled('style'), fn ($q) => $q->where('g.style', 'like', '%' . $request->style . '%'))
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
            ->when($request->filled('style'), fn ($q) => $q->where('i.style', 'like', '%' . $request->style . '%'))
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

        return view('sfl-inventory::admin.reports.buyer-style-wise', compact('rows', 'styleSpans', 'buyerSpans', 'buyers', 'items'));
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

        $lines = InvGrnItem::query()
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
            })
            ->orderBy('inv_grn_items.expiry_date')
            ->paginate($request->boolean('print') ? 100000 : 30)
            ->withQueryString();

        $items = InvItem::active()->orderBy('item_name')->get();
        $stores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.reports.expiry-tracking', compact('lines', 'items', 'stores', 'withinDays'));
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
            ->when($request->filled('style'), fn ($q) => $q->where('style', 'like', '%' . $request->style . '%'))
            ->when($request->filled('item_id'), fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('item_id', $request->item_id)))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('issue_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('issue_date', '<=', $request->date_to))
            ->latest('issue_date')
            ->paginate($request->boolean('print') ? 100000 : 30)
            ->withQueryString();

        $departments = InvDepartment::active()->orderBy('name')->get();
        $buyers = InvBuyer::active()->orderBy('name')->get();
        $items = InvItem::active()->orderBy('item_name')->get();

        return view('sfl-inventory::admin.reports.issue-report', compact('issues', 'departments', 'buyers', 'items'));
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
                $item->latest_rate = $this->stock->latestRate($item->id);
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
            ');

        $rows = DB::query()->fromSub($withBalance, 'x')
            ->where('x.transaction_type', 'not like', '%\_reversal')
            ->orderBy('x.item_name')
            ->orderBy('x.transaction_date')
            ->orderBy('x.id')
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
