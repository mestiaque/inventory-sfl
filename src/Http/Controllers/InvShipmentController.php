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

        $shipments = InvShipment::query()
            ->with(['buyer', 'gatePasses', 'store', 'creator', 'items.item.unit'])
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

    /**
     * Shipment is the first document in the FG->Gate Pass flow: what's
     * going out, how much, to whom — plus invoice/packing list. It never
     * posts stock itself; the Gate Pass created against it afterward is
     * the actual stock-out / security-exit event (see
     * InvGatePassController::approve()).
     */
    public function create(): View
    {
        $this->authorize('inv_shipment.add');

        return view('sfl-inventory::admin.shipments.create', $this->formOptions());
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
                'store_id'        => $data['store_id'],
                'status'          => 'pending',
                'remarks'         => $data['remarks'] ?? null,
                'created_by'      => auth()->id(),
            ]);

            foreach ($data['items'] as $line) {
                $shipment->items()->create(['item_id' => $line['item_id'], 'quantity' => $line['quantity']]);
            }

            return $shipment;
        });

        return redirect()->route('inventory.shipments.index')->with('success', "Shipment {$shipment->shipment_no} created. Issue a Gate Pass against it to release the goods.");
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
        ];
    }
}
