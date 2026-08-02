<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvShipmentRequest;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Models\InvGatePass;
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

        // Note: store_id is only set on direct shipments (no gate pass) — a
        // Store Incharge won't see gate-pass-linked shipments here even if
        // the linked gate pass was from their store, since the shipment row
        // itself carries no store reference in that case.
        $shipments = InvShipment::query()
            ->with(['buyer', 'gatePass'])
            ->when($request->filled('search'), fn ($q) => $q->where('shipment_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('shipment_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('shipment_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'created_by'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $buyers = InvBuyer::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.shipments.index', compact('shipments', 'buyers'));
    }

    public function create(Request $request): View
    {
        $this->authorize('inv_shipment.add');

        $gatePass = null;
        if ($request->filled('gate_pass_id')) {
            $gatePass = InvGatePass::with('items.item')->where('status', 'issued')->find($request->gate_pass_id);
        }

        return view('sfl-inventory::admin.shipments.create', ['gatePass' => $gatePass] + $this->formOptions());
    }

    public function store(InvShipmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $shipment = DB::transaction(function () use ($data) {
            $shipment = InvShipment::create([
                'shipment_date'   => $data['shipment_date'],
                'buyer_id'        => $data['buyer_id'] ?? null,
                'invoice_no'      => $data['invoice_no'] ?? null,
                'packing_list_no' => $data['packing_list_no'] ?? null,
                'gate_pass_id'    => $data['gate_pass_id'] ?? null,
                'store_id'        => $data['store_id'] ?? null,
                'status'          => 'pending',
                'remarks'         => $data['remarks'] ?? null,
                'created_by'      => auth()->id(),
            ]);

            foreach ($data['items'] as $line) {
                $shipmentItem = $shipment->items()->create(['item_id' => $line['item_id'], 'quantity' => $line['quantity']]);

                // A shipment linked to a gate pass posts nothing — stock already
                // left via the gate pass. Only a direct shipment (no gate pass)
                // is itself the stock-out event.
                if (! $shipment->gate_pass_id) {
                    $this->stock->post([
                        'item_id'          => $shipmentItem->item_id,
                        'store_id'         => $shipment->store_id,
                        'transaction_date' => $shipment->shipment_date,
                        'transaction_type' => 'shipment',
                        'qty_out'          => $shipmentItem->quantity,
                        'reference_type'   => 'inv_shipment',
                        'reference_id'     => $shipment->id,
                        'remarks'          => "Shipment {$shipment->shipment_no}",
                        'created_by'       => $shipment->created_by,
                    ]);
                }
            }

            return $shipment;
        });

        return redirect()->route('inventory.shipments.index')->with('success', "Shipment {$shipment->shipment_no} created successfully.");
    }

    public function updateStatus(Request $request, InvShipment $shipment): RedirectResponse
    {
        $this->authorize('inv_shipment.edit');

        $request->validate(['status' => ['required', 'in:pending,dispatched,delivered']]);

        $shipment->update(['status' => $request->status]);

        return back()->with('success', 'Shipment status updated.');
    }

    private function formOptions(): array
    {
        return [
            'buyers'     => InvBuyer::active()->orderBy('name')->get(),
            'stores'     => InvStore::active()->orderBy('name')->get(),
            'gatePasses' => InvGatePass::where('status', 'issued')->orderByDesc('id')->get(),
            'items'      => InvItem::active()->ofType('finished_good')->orderBy('item_name')->get(),
        ];
    }
}
