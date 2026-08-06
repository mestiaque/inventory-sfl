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

        $gatePasses = InvGatePass::query()
            ->with(['buyer', 'store', 'creator', 'shipment', 'items.item.unit'])
            ->when($request->filled('search'), fn ($q) => $q->where('gate_pass_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('gate_pass_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('gate_pass_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'created_by'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $buyers = InvBuyer::active()->orderBy('name')->get();
        $stores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.gate-passes.index', compact('gatePasses', 'buyers', 'stores'));
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
                $gatePass->items()->create(['item_id' => $line['item_id'], 'quantity' => $line['quantity']]);
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

    private function formOptions(): array
    {
        $fgStore = InvStore::active()->where('type', 'finished_goods')->first();

        return [
            'buyers'  => InvBuyer::active()->orderBy('name')->get(),
            'stores'  => InvStore::active()->orderBy('name')->get(),
            'fgStore' => $fgStore,
            'items'   => InvItem::active()->ofType('finished_good')->orderBy('item_name')->get(),
        ];
    }
}
