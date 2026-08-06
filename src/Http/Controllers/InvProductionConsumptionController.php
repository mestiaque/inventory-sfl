<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvProductionConsumptionRequest;
use ME\SflInventory\Models\InvDepartment;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvProductionConsumption;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\StockService;

class InvProductionConsumptionController extends Controller
{
    public function __construct(private readonly StockService $stock)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_production.list');

        $consumptions = InvProductionConsumption::query()
            ->with(['department', 'store', 'issue', 'creator', 'items.item.unit'])
            ->when($request->filled('search'), fn ($q) => $q->where('consumption_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('consumption_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('consumption_date', '<=', $request->date_to))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $departments = InvDepartment::active()->orderBy('name')->get();
        $stores = InvStore::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.production-consumptions.index', compact('consumptions', 'departments', 'stores'));
    }

    public function create(): View
    {
        $this->authorize('inv_production.add');

        return view('sfl-inventory::admin.production-consumptions.create', $this->formOptions());
    }

    public function store(InvProductionConsumptionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $consumption = DB::transaction(function () use ($data) {
            $consumption = InvProductionConsumption::create([
                'department_id'    => $data['department_id'],
                'store_id'         => $data['store_id'],
                'issue_id'         => $data['issue_id'] ?? null,
                'style'            => $data['style'] ?? null,
                'order_ref'        => $data['order_ref'] ?? null,
                'consumption_date' => $data['consumption_date'],
                'remarks'          => $data['remarks'] ?? null,
                'created_by'       => auth()->id(),
            ]);

            foreach ($data['items'] as $line) {
                $consumedQty = (float) $line['consumed_qty'];
                $wasteQty = (float) ($line['waste_qty'] ?? 0);

                $consumption->items()->create([
                    'item_id'      => $line['item_id'],
                    'consumed_qty' => $consumedQty,
                    'waste_qty'    => $wasteQty,
                ]);

                // A single combined outflow — the consumed-vs-waste split stays
                // queryable from inv_production_consumption_items for reporting.
                if (($consumedQty + $wasteQty) > 0) {
                    $this->stock->post([
                        'item_id'          => $line['item_id'],
                        'store_id'         => $consumption->store_id,
                        'transaction_date' => $consumption->consumption_date,
                        'transaction_type' => 'production_consumption',
                        'qty_out'          => $consumedQty + $wasteQty,
                        'department_id'    => $consumption->department_id,
                        'reference_type'   => 'inv_production_consumption',
                        'reference_id'     => $consumption->id,
                        'remarks'          => "Production Consumption {$consumption->consumption_no}",
                        'created_by'       => $consumption->created_by,
                    ]);
                }
            }

            return $consumption;
        });

        return redirect()->route('inventory.production-consumptions.index')->with('success', "Consumption {$consumption->consumption_no} posted and stock updated.");
    }

    private function formOptions(): array
    {
        return [
            'departments' => InvDepartment::active()->orderBy('name')->get(),
            'stores'      => InvStore::active()->orderBy('name')->get(),
            'items'       => InvItem::active()->orderBy('item_name')->get(),
        ];
    }
}
