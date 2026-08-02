<?php

namespace ME\SflInventory\Http\Controllers;

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

        $receives = InvFinishedGoodsReceive::query()
            ->with(['buyer', 'store'])
            ->when($request->filled('search'), fn ($q) => $q->where('receive_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('receive_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('receive_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'created_by'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $buyers = InvBuyer::active()->orderBy('name')->get();
        $stores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.fg-receives.index', compact('receives', 'buyers', 'stores'));
    }

    public function create(): View
    {
        $this->authorize('inv_fg_receive.add');

        return view('sfl-inventory::admin.fg-receives.create', $this->formOptions());
    }

    public function store(InvFinishedGoodsReceiveRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $receive = DB::transaction(function () use ($data) {
            $receive = InvFinishedGoodsReceive::create([
                'receive_date' => $data['receive_date'],
                'style'        => $data['style'] ?? null,
                'buyer_id'     => $data['buyer_id'] ?? null,
                'order_ref'    => $data['order_ref'] ?? null,
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

    private function formOptions(): array
    {
        return [
            'buyers' => InvBuyer::active()->orderBy('name')->get(),
            'stores' => InvStore::active()->orderBy('name')->get(),
            'items'  => InvItem::active()->ofType('finished_good')->orderBy('item_name')->get(),
        ];
    }
}
