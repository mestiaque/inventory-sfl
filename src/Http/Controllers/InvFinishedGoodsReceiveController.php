<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvFinishedGoodsReceiveRequest;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Models\InvFinishedGoodsReceive;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\InvOperatorScopeService;
use ME\SflInventory\Services\MerchandisingLink;
use ME\SflInventory\Services\StockService;

class InvFinishedGoodsReceiveController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
        private readonly InvOperatorScopeService $operatorScope,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_fg_receive.list');

        $receivesQuery = InvFinishedGoodsReceive::query()
            ->with(['buyer', 'store', 'creator', 'items.item.unit'])
            ->when($request->filled('search'), fn ($q) => $q->where('receive_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('item_id'), fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('item_id', $request->item_id)))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('receive_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('receive_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'created_by'));

        // Total qty over every filtered document (just the picked item's lines when filtered by item).
        $grandQty = (float) \Illuminate\Support\Facades\DB::table('inv_finished_goods_receive_items')
            ->whereIn('fg_receive_id', (clone $receivesQuery)->reorder()->select('id'))
            ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->item_id))
            ->sum('quantity');
        $filterItems = \ME\SflInventory\Models\InvItem::active()->orderBy('item_name')->get(['id', 'item_code', 'item_name']);

        $receives = $receivesQuery
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $buyers = InvBuyer::active()->orderBy('name')->get();
        $stores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.fg-receives.index', compact('receives', 'buyers', 'stores', 'grandQty', 'filterItems'));
    }

    public function create(): View
    {
        $this->authorize('inv_fg_receive.add');

        return view('sfl-inventory::admin.fg-receives.create', $this->formOptions());
    }

    public function store(InvFinishedGoodsReceiveRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Merchandising-linked: inventory buyer, style text and order ref come
        // from the Merchandising buyer / style / PO picked — never retyped.
        $link = app(MerchandisingLink::class);
        if ($link->available() && ! empty($data['msfl_buyer_id']) && ! empty($data['msfl_style_id'])) {
            $data['buyer_id'] = $link->inventoryBuyerId((int) $data['msfl_buyer_id']);
            $data['style'] = $link->styleNo((int) $data['msfl_style_id']);
            $data['order_ref'] = ! empty($data['msfl_order_po_id']) ? $link->orderRef((int) $data['msfl_order_po_id']) : null;
        }

        $receive = DB::transaction(function () use ($data) {
            $receive = InvFinishedGoodsReceive::create([
                'receive_date' => $data['receive_date'],
                'style'        => $data['style'] ?? null,
                'buyer_id'     => $data['buyer_id'] ?? null,
                'order_ref'    => $data['order_ref'] ?? null,
                'msfl_style_id'              => $data['msfl_style_id'] ?? null,
                'msfl_order_po_id'  => $data['msfl_order_po_id'] ?? null,
                'msfl_buyer_id'              => $data['msfl_buyer_id'] ?? null,
                'store_id'     => $data['store_id'],
                'remarks'      => $data['remarks'] ?? null,
                'created_by'   => auth()->id(),
            ]);

            foreach ($data['items'] as $line) {
                $receiveItem = $receive->items()->create(['item_id' => $line['item_id'], 'quantity' => $line['quantity']]);

                $this->stock->post([
                    'item_id'          => $receiveItem->item_id,
                    'store_id'         => $receive->store_id,
                    'transaction_date' => $receive->receive_date,
                    'transaction_type' => 'finished_goods',
                    'qty_in'           => $receiveItem->quantity,
                    'reference_type'   => 'inv_finished_goods_receive',
                    'reference_id'     => $receive->id,
                    'remarks'          => "FG Receive {$receive->receive_no}",
                    'created_by'       => $receive->created_by,
                ]);
            }

            return $receive;
        });

        return redirect()->route('inventory.fg-receives.index')->with('success', "Finished goods receive {$receive->receive_no} posted and stock updated.");
    }

    /**
     * For the receive form: how many pieces production has packed for the
     * style / PO, how many are already in the Finish Store, and what's left.
     */
    public function productionSummary(Request $request): JsonResponse
    {
        $this->authorize('inv_fg_receive.add');

        $link = app(MerchandisingLink::class);
        $styleId = (int) $request->input('style_id');
        abort_unless($link->available() && $styleId, 404);

        return response()->json($link->finishSummary($styleId, (int) $request->input('po_id') ?: null));
    }

    private function formOptions(): array
    {
        // Finished goods only ever go into the one store marked "For
        // Finished Goods" — locked here rather than left as a free choice.
        $fgStore = InvStore::active()->where('type', InvStore::TYPE_FINISH)->first();

        return [
            'buyers'  => InvBuyer::active()->orderBy('name')->get(),
            'stores'  => InvStore::active()->orderBy('name')->get(),
            'fgStore' => $fgStore,
            'items'   => InvItem::active()->ofType('finished_good')->orderBy('item_name')->get(),
            // Buyer -> Style -> PO from Merchandising (see MerchandisingLink).
            'merLinked'                  => app(MerchandisingLink::class)->available(),
            'merBuyersOptions'           => app(MerchandisingLink::class)->buyers(),
            'merStylesOptions'           => app(MerchandisingLink::class)->styles(),
            'merOrderPosOptions' => app(MerchandisingLink::class)->pos(),
        ];
    }
}
