<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvStockAdjustmentRequest;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvStockAdjustment;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\InvOperatorScopeService;
use ME\SflInventory\Services\StockService;

class InvStockAdjustmentController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
        private readonly InvOperatorScopeService $operatorScope,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_adjustment.list');

        $adjustments = InvStockAdjustment::query()
            ->with('store')
            ->when($request->filled('search'), fn ($q) => $q->where('adjustment_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('adjustment_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('adjustment_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'created_by'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.adjustments.index', compact('adjustments', 'stores'));
    }

    public function create(): View
    {
        $this->authorize('inv_adjustment.add');

        return view('sfl-inventory::admin.adjustments.create', $this->formOptions());
    }

    public function store(InvStockAdjustmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $adjustment = DB::transaction(function () use ($data) {
            $adjustment = InvStockAdjustment::create([
                'store_id'         => $data['store_id'],
                'adjustment_date'  => $data['adjustment_date'],
                'type'             => $data['type'],
                'status'           => 'pending',
                'remarks'          => $data['remarks'] ?? null,
                'created_by'       => auth()->id(),
            ]);

            foreach ($data['items'] as $line) {
                // Snapshot the system quantity now, at the moment of counting —
                // this is the only "current stock" read this package ever
                // freezes to a column, and only as an audit record of what the
                // system said at count time, not a live balance.
                $systemQty = $this->stock->currentStock($line['item_id'], $data['store_id']);
                $physicalQty = (float) $line['physical_qty'];

                $adjustment->items()->create([
                    'item_id'        => $line['item_id'],
                    'system_qty'     => $systemQty,
                    'physical_qty'   => $physicalQty,
                    'difference_qty' => $physicalQty - $systemQty,
                ]);
            }

            return $adjustment;
        });

        return redirect()->route('inventory.adjustments.index')->with('success', "Adjustment {$adjustment->adjustment_no} submitted for approval.");
    }

    public function approve(InvStockAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('inv_adjustment.approve');

        abort_if($adjustment->status !== 'pending', 403, 'Only pending adjustments can be approved.');

        DB::transaction(function () use ($adjustment) {
            foreach ($adjustment->items as $line) {
                if ((float) $line->difference_qty === 0.0) {
                    continue;
                }

                // Both shortage and excess are valued at the item's current
                // moving-average cost — StockService only defaults outflows to
                // that rate, so it's passed explicitly here for the qty_in
                // (excess) case too.
                $this->stock->post([
                    'item_id'          => $line->item_id,
                    'store_id'         => $adjustment->store_id,
                    'transaction_date' => $adjustment->adjustment_date,
                    'transaction_type' => 'adjustment',
                    'qty_in'           => $line->difference_qty > 0 ? $line->difference_qty : 0,
                    'qty_out'          => $line->difference_qty < 0 ? abs($line->difference_qty) : 0,
                    'rate'             => $this->stock->averageRate($line->item_id, $adjustment->store_id),
                    'reference_type'   => 'inv_stock_adjustment',
                    'reference_id'     => $adjustment->id,
                    'remarks'          => "Adjustment {$adjustment->adjustment_no} ({$adjustment->type})",
                    'created_by'       => auth()->id(),
                ]);
            }

            $adjustment->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        });

        return back()->with('success', "Adjustment {$adjustment->adjustment_no} approved and stock updated.");
    }

    public function reject(InvStockAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('inv_adjustment.approve');

        abort_if($adjustment->status !== 'pending', 403, 'Only pending adjustments can be rejected.');

        $adjustment->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        return back()->with('success', "Adjustment {$adjustment->adjustment_no} rejected.");
    }

    /**
     * Pending/rejected adjustments never posted to stock, so deleting them
     * is a plain delete. An approved one already moved stock — reverse it
     * first (guarded so it can't push the store negative if that stock has
     * since been used elsewhere), then delete.
     */
    public function destroy(InvStockAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('inv_adjustment.delete');

        $adjustment->load('items');

        if ($adjustment->status === 'approved') {
            try {
                foreach ($adjustment->items as $line) {
                    if ((float) $line->difference_qty > 0) {
                        $available = $this->stock->currentStock($line->item_id, $adjustment->store_id);
                        if ($available < $line->difference_qty) {
                            throw ValidationException::withMessages([
                                'items' => 'Cannot delete this adjustment: "' . ($line->item?->item_name ?? "item #{$line->item_id}") . '" only has ' . inv_qty($available) . ' left in stock, but this adjustment added ' . inv_qty($line->difference_qty) . ' — some has already been used elsewhere. Post a new Stock Adjustment instead.',
                            ]);
                        }
                    }
                }
            } catch (ValidationException $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        DB::transaction(function () use ($adjustment) {
            if ($adjustment->status === 'approved') {
                foreach ($adjustment->items as $line) {
                    if ((float) $line->difference_qty === 0.0) {
                        continue;
                    }

                    $this->stock->post([
                        'item_id'          => $line->item_id,
                        'store_id'         => $adjustment->store_id,
                        'transaction_date' => now()->toDateString(),
                        'transaction_type' => 'adjustment_reversal',
                        'qty_in'           => $line->difference_qty < 0 ? abs($line->difference_qty) : 0,
                        'qty_out'          => $line->difference_qty > 0 ? $line->difference_qty : 0,
                        'rate'             => $this->stock->averageRate($line->item_id, $adjustment->store_id),
                        'reference_type'   => 'inv_stock_adjustment',
                        'reference_id'     => $adjustment->id,
                        'remarks'          => "Reversal of Adjustment {$adjustment->adjustment_no}",
                        'created_by'       => auth()->id(),
                    ]);
                }
            }

            $adjustment->delete();
        });

        return redirect()->route('inventory.adjustments.index')->with('success', "Adjustment {$adjustment->adjustment_no} deleted" . ($adjustment->status === 'approved' ? ' and stock reversed.' : '.'));
    }

    private function formOptions(): array
    {
        return [
            'stores' => InvStore::active()->orderBy('name')->get(),
            'items'  => InvItem::active()->orderBy('item_name')->get(),
        ];
    }
}
