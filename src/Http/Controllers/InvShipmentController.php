<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvShipmentRequest;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvShipment;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\InvOperatorScopeService;
use ME\SflInventory\Services\StockService;

class InvShipmentController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
        private readonly InvOperatorScopeService $operatorScope,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_shipment.list');

        $shipmentsQuery = InvShipment::query()
            ->with(['buyer', 'gatePasses', 'store', 'creator', 'items.item.unit'])
            ->when($request->filled('search'), fn ($q) => $q->where('shipment_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('item_id'), fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('item_id', $request->item_id)))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('shipment_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('shipment_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'created_by'));

        // Total qty over every filtered document (just the picked item's lines when filtered by item).
        $grandQty = (float) \Illuminate\Support\Facades\DB::table('inv_shipment_items')
            ->whereIn('shipment_id', (clone $shipmentsQuery)->reorder()->select('id'))
            ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->item_id))
            ->sum('quantity');
        $filterItems = \ME\SflInventory\Models\InvItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name']);

        $shipments = $shipmentsQuery
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $buyers = InvBuyer::active()->orderBy('name')->get();
        $poLabels = app(\ME\SflInventory\Services\MerchandisingLink::class)->shipmentPos()->map(fn ($po) => $po['label']);

        return view('sfl-inventory::admin.shipments.index', compact('shipments', 'buyers', 'grandQty', 'filterItems', 'poLabels'));
    }

    /**
     * Shipment is the first document in the FG->Gate Pass flow: what's
     * going out, how much, to whom — plus invoice/packing list. It never
     * posts stock itself; the Gate Pass created against it afterward is
     * the actual stock-out / security-exit event (see
     * InvGatePassController::approve()).
     */
    public function create(Request $request): View
    {
        $this->authorize('inv_shipment.add');

        // ?msfl_invoice_id= : fill the form from a Merchandising commercial invoice (no retyping).
        $link = app(\ME\SflInventory\Services\MerchandisingLink::class);
        if ($request->filled('msfl_invoice_id') && ! $request->session()->hasOldInput()
            && ($prefill = $link->shipmentFromInvoice($request->integer('msfl_invoice_id')))) {
            $request->session()->flashInput($prefill);
        }
        // ?msfl_buyer_po= : every style / color line under one buyer PO no.
        if ($request->filled('msfl_buyer_po') && ! $request->filled('msfl_invoice_id') && ! $request->session()->hasOldInput()
            && ($prefill = $link->shipmentFromBuyerPo((string) $request->input('msfl_buyer_po')))) {
            $request->session()->flashInput($prefill);
        }

        return view('sfl-inventory::admin.shipments.create', $this->formOptions() + ['commercialInvoices' => $link->commercialInvoices(), 'buyerPoNumbers' => $link->buyerPoNumbers()]);
    }

    public function store(InvShipmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $shipment = DB::transaction(function () use ($data) {
            $this->stock->assertInStock(collect($data['items'])->map(fn ($l) => ['item_id' => $l['item_id'], 'qty' => $l['quantity']]), (int) $data['store_id'], 'ship');
            $this->assertWithinFinishReceive($data['items']);

            $shipment = InvShipment::create([
                'shipment_date'   => $data['shipment_date'],
                'buyer_id'        => $data['buyer_id'] ?? null,
                'invoice_no'      => $data['invoice_no'] ?? null,
                'packing_list_no' => $data['packing_list_no'] ?? null,
                'store_id'        => $data['store_id'],
                'status'          => 'pending',
                'remarks'         => $data['remarks'] ?? null,
                'created_by'      => auth()->id(),
            ]);

            foreach ($data['items'] as $line) {
                $shipment->items()->create(['item_id' => $line['item_id'], 'quantity' => $line['quantity'], 'msfl_order_po_id' => $line['msfl_order_po_id'] ?? null]);
            }

            return $shipment;
        });

        // Next step right away: the Gate Pass releases the goods and prints the Delivery Challan.
        if (auth()->user()?->can('inv_gate_pass.add')) {
            return redirect()->route('inventory.gate-passes.create', ['shipment_id' => $shipment->id])
                ->with('success', "Shipment {$shipment->shipment_no} saved. Now issue the Gate Pass — it releases the goods and prints the Delivery Challan.");
        }

        return redirect()->route('inventory.shipments.index')->with('success', "Shipment {$shipment->shipment_no} created. Issue a Gate Pass against it to release the goods.");
    }

    public function updateStatus(Request $request, InvShipment $shipment): RedirectResponse
    {
        $this->authorize('inv_shipment.edit');

        $request->validate(['status' => ['required', 'in:pending,dispatched,delivered']]);

        $shipment->update(['status' => $request->status]);

        return back()->with('success', 'Shipment status updated.');
    }

    /** Per PO: what's shipped (this + earlier shipments) can't pass what was received into the Finish Store for it. */
    private function assertWithinFinishReceive(array $items): void
    {
        $byPo = collect($items)->filter(fn ($l) => ! empty($l['msfl_order_po_id']))->groupBy('msfl_order_po_id')->map(fn ($g) => (float) $g->sum('quantity'));
        if ($byPo->isEmpty()) {
            return;
        }
        $received = DB::table('inv_finished_goods_receives as r')->join('inv_finished_goods_receive_items as ri', 'ri.fg_receive_id', '=', 'r.id')
            ->whereNull('r.deleted_at')->whereIn('r.msfl_order_po_id', $byPo->keys())
            ->groupBy('r.msfl_order_po_id')->selectRaw('r.msfl_order_po_id po, SUM(ri.quantity) q')->pluck('q', 'po');
        $shipped = DB::table('inv_shipment_items as si')->join('inv_shipments as s', 's.id', '=', 'si.shipment_id')
            ->whereNull('s.deleted_at')->whereIn('si.msfl_order_po_id', $byPo->keys())
            ->groupBy('si.msfl_order_po_id')->selectRaw('si.msfl_order_po_id po, SUM(si.quantity) q')->pluck('q', 'po');
        $pos = DB::table('msfl_order_pos')->whereIn('id', $byPo->keys())->get(['id', 'po_no', 'style_id'])->keyBy('id');
        // A style received without a PO can't be split per PO — the store stock check alone covers those.
        $styleLevel = DB::table('inv_finished_goods_receives')->whereNull('deleted_at')->whereNull('msfl_order_po_id')
            ->whereIn('msfl_style_id', $pos->pluck('style_id'))->distinct()->pluck('msfl_style_id')->flip();

        foreach ($byPo as $poId => $want) {
            if (isset($styleLevel[$pos[$poId]->style_id ?? 0])) {
                continue;
            }
            $poNo = [$poId => $pos[$poId]->po_no ?? $poId];
            $left = (float) ($received[$poId] ?? 0) - (float) ($shipped[$poId] ?? 0);
            if ($want > $left + 0.0001) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'PO ' . ($poNo[$poId] ?? $poId) . ': ' . inv_qty($received[$poId] ?? 0) . ' received into the Finish Store, ' . inv_qty($shipped[$poId] ?? 0)
                        . ' already on shipments, so only ' . inv_qty(max(0, $left)) . ' can be shipped now (you entered ' . inv_qty($want) . ').',
                ]);
            }
        }
    }

    private function formOptions(): array
    {
        $items = InvItem::active()->ofType('finished_good')->with('unit')->orderBy('item_name')->get();

        // item_id => [store_id => available qty] — lets the create form show
        // "Available: X UNIT" per item/store and cap the quantity input
        // without a round-trip per keystroke.
        $stockMap = DB::table('inv_stock_transactions')
            ->whereIn('item_id', $items->pluck('id'))
            ->selectRaw('item_id, store_id, SUM(qty_in) - SUM(qty_out) as balance')
            ->groupBy('item_id', 'store_id')
            ->get()
            ->groupBy('item_id')
            ->map(fn ($rows) => $rows->pluck('balance', 'store_id'));

        $fgStore = InvStore::active()->where('type', 'finished_goods')->first();

        return [
            'buyers'   => InvBuyer::active()->orderBy('name')->get(),
            'stores'   => InvStore::active()->orderBy('name')->get(),
            'fgStore'  => $fgStore,
            'items'    => $items,
            'stockMap' => $stockMap,
            'shipmentPos' => app(\ME\SflInventory\Services\MerchandisingLink::class)->shipmentPos(),
        ] + app(\ME\SflInventory\Services\MerchandisingLink::class)->formOptions();
    }
}
