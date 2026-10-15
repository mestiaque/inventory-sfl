<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvGatePassRequest;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Models\InvGatePass;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvShipment;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\InvOperatorScopeService;
use ME\SflInventory\Services\StockService;

class InvGatePassController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
        private readonly InvOperatorScopeService $operatorScope,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_gate_pass.list');

        $gatePassesQuery = InvGatePass::query()
            ->with(['buyer', 'store', 'creator', 'shipment', 'items.item.unit'])
            ->when($request->filled('search'), fn ($q) => $q->where('gate_pass_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('item_id'), fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('item_id', $request->item_id)))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('gate_pass_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('gate_pass_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'created_by'));

        // Total qty over every filtered document (just the picked item's lines when filtered by item).
        $grandQty = (float) \Illuminate\Support\Facades\DB::table('inv_gate_pass_items')
            ->whereIn('gate_pass_id', (clone $gatePassesQuery)->reorder()->select('id'))
            ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->item_id))
            ->sum('quantity');
        $filterItems = \ME\SflInventory\Models\InvItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name']);

        $gatePasses = $gatePassesQuery
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $buyers = InvBuyer::active()->orderBy('name')->get();
        $stores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.gate-passes.index', compact('gatePasses', 'buyers', 'stores', 'grandQty', 'filterItems'));
    }

    /**
     * A gate pass is normally created against an existing Shipment (passed
     * as ?shipment_id=) — the shipment already says what/how much/for
     * whom; the gate pass just adds vehicle/driver and is the actual
     * security-exit permission. A gate pass with no shipment is still
     * supported for ad-hoc/direct exits.
     */
    public function create(Request $request): View
    {
        $this->authorize('inv_gate_pass.add');

        $shipment = null;
        if ($request->filled('shipment_id')) {
            $shipment = InvShipment::with('items.item')->find($request->shipment_id);
        }

        return view('sfl-inventory::admin.gate-passes.create', ['shipment' => $shipment] + $this->formOptions());
    }

    public function store(InvGatePassRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $gatePass = DB::transaction(function () use ($data) {
            $gatePass = InvGatePass::create([
                'shipment_id'    => $data['shipment_id'] ?? null,
                'gate_pass_date' => $data['gate_pass_date'],
                'buyer_id'       => $data['buyer_id'] ?? null,
                'vehicle_no'     => $data['vehicle_no'] ?? null,
                'driver_name'    => $data['driver_name'] ?? null,
                'driver_contact' => $data['driver_contact'] ?? null,
                'store_id'       => $data['store_id'],
                'status'         => 'pending',
                'remarks'        => $data['remarks'] ?? null,
                'created_by'     => auth()->id(),
            ]);

            foreach ($data['items'] as $line) {
                $gatePass->items()->create(['item_id' => $line['item_id'], 'quantity' => $line['quantity'], 'carton_qty' => $line['carton_qty'] ?? null]);
            }

            return $gatePass;
        });

        return redirect()->route('inventory.gate-passes.index')->with('success', "Gate pass {$gatePass->gate_pass_no} created. Approve to release goods.");
    }

    /**
     * The real stock-out event: goods physically leave the factory's tracked
     * custody here. The Shipment this gate pass may be linked to is purely
     * a logistics/status record — it never posts stock itself (see
     * InvShipmentController::store()).
     */
    public function approve(InvGatePass $gate_pass): RedirectResponse
    {
        $this->authorize('inv_gate_pass.approve');

        abort_if($gate_pass->status !== 'pending', 403, 'Only pending gate passes can be issued.');

        DB::transaction(function () use ($gate_pass) {
            foreach ($gate_pass->items as $line) {
                $this->stock->post([
                    'item_id'          => $line->item_id,
                    'store_id'         => $gate_pass->store_id,
                    'transaction_date' => $gate_pass->gate_pass_date,
                    'transaction_type' => 'gate_pass',
                    'qty_out'          => $line->quantity,
                    'reference_type'   => 'inv_gate_pass',
                    'reference_id'     => $gate_pass->id,
                    'remarks'          => "Gate Pass {$gate_pass->gate_pass_no}",
                    'created_by'       => auth()->id(),
                ]);
            }

            $gate_pass->update(['status' => 'issued']);
        });

        return back()->with('success', "Gate pass {$gate_pass->gate_pass_no} issued and stock updated.");
    }

    /**
     * The printed shipment delivery challan handed to the transporter — the
     * Gate Pass is the actual stock-out document; each line's PO/Style/Colour
     * and size-wise quantity come from the Shipment it was issued against (a
     * Gate Pass item itself only carries item/qty/carton — see
     * PackingFigures::forPo(), the same size breakdown the Final Packing
     * List prints).
     */
    public function print(InvGatePass $gate_pass): View
    {
        $this->authorize('inv_gate_pass.print');

        $gate_pass->load(['buyer', 'store', 'creator', 'shipment', 'items.item.unit']);

        $posByItemId = collect();
        $figuresByItemId = collect();
        $sizes = collect();
        if ($gate_pass->shipment) {
            $link = app(\ME\SflInventory\Services\MerchandisingLink::class);
            $poIds = $gate_pass->shipment->items->pluck('msfl_order_po_id')->filter()->unique()->values();
            if ($link->available()) {
                $pos = \ME\MerchandisingSfl\Models\OrderPo::query()->with(['style', 'color', 'sizes.size'])->whereIn('id', $poIds)->get()->keyBy('id');
                // Gate pass line → its shipment line's PO: same position (the gate pass copies the
                // shipment's lines in order — one style item can carry several POs / colors), else by item.
                $shipLines = $gate_pass->shipment->items->values();
                $samePlaces = $shipLines->count() === $gate_pass->items->count()
                    && $shipLines->pluck('item_id')->all() === $gate_pass->items->pluck('item_id')->all();
                $posByItemId = $gate_pass->items->values()->mapWithKeys(fn ($line, $i) => [$line->id => $pos->get(
                    $samePlaces ? $shipLines[$i]->msfl_order_po_id : $shipLines->firstWhere('item_id', $line->item_id)?->msfl_order_po_id
                )]);

                $figures = app(\ME\MerchandisingSfl\Services\PackingFigures::class);
                $shipped = $shipLines->groupBy('msfl_order_po_id')->map(fn ($l) => (float) $l->sum('quantity'));
                $invoiced = $this->invoiceSizes($gate_pass->shipment->invoice_no);
                // Size split of what goes out: the commercial invoice's carton list when the shipment
                // carries its invoice no; else production's packed qty only when the whole PO ships.
                $figuresByItemId = $posByItemId->filter()->map(function ($po) use ($figures, $invoiced, $shipped) {
                    $f = $figures->forPo($po);
                    if (isset($invoiced[$po->id])) {
                        return ['packed' => $invoiced[$po->id]['sizes'], 'cartons' => $invoiced[$po->id]['cartons']] + $f;
                    }

                    return array_sum($f['packed']) == ($shipped[$po->id] ?? 0) ? $f : ['packed' => []] + $f;
                });
                $sizes = \ME\MerchandisingSfl\Models\Size::displaySort($figuresByItemId->flatMap(fn ($f) => $f['sizes'])->unique('id'));
            }
        }

        return view('sfl-inventory::admin.gate-passes.print', compact('gate_pass', 'posByItemId', 'figuresByItemId', 'sizes'));
    }

    /** order_po_id => [sizes: size_id => pcs, cartons] from the Merchandising commercial invoice with this number. */
    private function invoiceSizes(?string $invoiceNo): array
    {
        if (! $invoiceNo || ! \Illuminate\Support\Facades\Schema::hasTable('msfl_com_invoice_carton_sizes')) {
            return [];
        }
        $out = [];
        $cartons = DB::table('msfl_com_invoice_cartons as c')->join('msfl_com_invoices as i', 'i.id', '=', 'c.invoice_id')
            ->whereNull('i.deleted_at')->where('i.invoice_no', $invoiceNo)->get(['c.id', 'c.order_po_id', 'c.carton_from', 'c.carton_to']);
        $sizes = DB::table('msfl_com_invoice_carton_sizes')->whereIn('invoice_carton_id', $cartons->pluck('id'))->get()->groupBy('invoice_carton_id');
        foreach ($cartons as $c) {
            $count = max(0, $c->carton_to - $c->carton_from + 1);
            $out[$c->order_po_id]['cartons'] = ($out[$c->order_po_id]['cartons'] ?? 0) + $count;
            foreach ($sizes[$c->id] ?? [] as $s) {
                $out[$c->order_po_id]['sizes'][$s->size_id] = ($out[$c->order_po_id]['sizes'][$s->size_id] ?? 0) + $s->qty * $count;
            }
        }

        return $out;
    }

    private function formOptions(): array
    {
        $fgStore = InvStore::active()->where('type', 'finished_goods')->first();

        return [
            'buyers'  => InvBuyer::active()->orderBy('name')->get(),
            'stores'  => InvStore::active()->orderBy('name')->get(),
            'fgStore' => $fgStore,
            'items'   => InvItem::active()->ofType('finished_good')->orderBy('item_name')->get(),
        ] + app(\ME\SflInventory\Services\MerchandisingLink::class)->formOptions();
    }
}
