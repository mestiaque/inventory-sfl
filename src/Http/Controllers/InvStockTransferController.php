<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvStockTransferReceiveRequest;
use ME\SflInventory\Http\Requests\InvStockTransferRequest;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvStockTransfer;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\InvOperatorScopeService;
use ME\SflInventory\Services\StockService;

class InvStockTransferController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
        private readonly InvOperatorScopeService $operatorScope,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_transfer.list');

        $transfers = InvStockTransfer::query()
            ->with(['fromStore', 'toStore'])
            ->when($request->filled('search'), fn ($q) => $q->where('transfer_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('from_store_id'), fn ($q) => $q->where('from_store_id', $request->from_store_id))
            ->when($request->filled('to_store_id'), fn ($q) => $q->where('to_store_id', $request->to_store_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('transfer_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('transfer_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToAnyStore($q, ['from_store_id', 'to_store_id'], 'created_by'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.transfers.index', compact('transfers', 'stores'));
    }

    public function create(): View
    {
        $this->authorize('inv_transfer.add');

        return view('sfl-inventory::admin.transfers.create', $this->formOptions());
    }

    public function store(InvStockTransferRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $transfer = DB::transaction(function () use ($data) {
            $transfer = InvStockTransfer::create([
                'from_store_id' => $data['from_store_id'],
                'to_store_id'   => $data['to_store_id'],
                'transfer_date' => $data['transfer_date'],
                'status'        => 'pending',
                'requested_by'  => auth()->id(),
                'remarks'       => $data['remarks'] ?? null,
                'created_by'    => auth()->id(),
            ]);

            foreach ($data['items'] as $line) {
                $transfer->items()->create(['item_id' => $line['item_id'], 'quantity' => $line['quantity']]);
            }

            return $transfer;
        });

        return redirect()->route('inventory.transfers.index')->with('success', "Transfer {$transfer->transfer_no} requested successfully.");
    }

    /**
     * Approving a transfer request dispatches it: stock leaves from_store_id
     * immediately (status -> in_transit), matching the spec's 3-step
     * Request -> Approval -> Receive workflow.
     */
    public function approve(InvStockTransfer $transfer): RedirectResponse
    {
        $this->authorize('inv_transfer.approve');

        abort_if($transfer->status !== 'pending', 403, 'Only pending transfers can be approved.');

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $line) {
                $this->stock->post([
                    'item_id'          => $line->item_id,
                    'store_id'         => $transfer->from_store_id,
                    'transaction_date' => $transfer->transfer_date,
                    'transaction_type' => 'transfer',
                    'qty_out'          => $line->quantity,
                    'reference_type'   => 'inv_stock_transfer',
                    'reference_id'     => $transfer->id,
                    'remarks'          => "Transfer {$transfer->transfer_no} dispatched",
                    'created_by'       => auth()->id(),
                ]);
            }

            $transfer->update(['status' => 'in_transit', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        });

        return back()->with('success', "Transfer {$transfer->transfer_no} approved and dispatched.");
    }

    public function reject(InvStockTransfer $transfer): RedirectResponse
    {
        $this->authorize('inv_transfer.approve');

        abort_if($transfer->status !== 'pending', 403, 'Only pending transfers can be rejected.');

        $transfer->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        return back()->with('success', "Transfer {$transfer->transfer_no} rejected.");
    }

    public function receiveForm(InvStockTransfer $transfer): View
    {
        $this->authorize('inv_transfer.receive');

        abort_if($transfer->status !== 'in_transit', 403, 'Only in-transit transfers can be received.');

        $transfer->load('items.item');

        return view('sfl-inventory::admin.transfers.receive', compact('transfer'));
    }

    public function receive(InvStockTransferReceiveRequest $request, InvStockTransfer $transfer): RedirectResponse
    {
        abort_if($transfer->status !== 'in_transit', 403, 'Only in-transit transfers can be received.');

        $data = $request->validated();

        DB::transaction(function () use ($data, $transfer) {
            foreach ($data['items'] as $line) {
                $item = $transfer->items()->findOrFail($line['id']);
                $item->update(['received_qty' => $line['received_qty']]);

                if ($line['received_qty'] > 0) {
                    $this->stock->post([
                        'item_id'          => $item->item_id,
                        'store_id'         => $transfer->to_store_id,
                        'transaction_date' => now()->toDateString(),
                        'transaction_type' => 'transfer',
                        'qty_in'           => $line['received_qty'],
                        'reference_type'   => 'inv_stock_transfer',
                        'reference_id'     => $transfer->id,
                        'remarks'          => "Transfer {$transfer->transfer_no} received",
                        'created_by'       => auth()->id(),
                    ]);
                }
            }

            $transfer->update(['status' => 'received', 'received_by' => auth()->id(), 'received_at' => now()]);
        });

        return redirect()->route('inventory.transfers.index')->with('success', "Transfer {$transfer->transfer_no} received.");
    }

    private function formOptions(): array
    {
        return [
            'stores' => InvStore::active()->orderBy('name')->get(),
            'items'  => InvItem::active()->orderBy('item_name')->get(),
        ];
    }
}
