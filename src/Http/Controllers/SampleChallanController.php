<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\SampleChallanRequest;
use ME\SflInventory\Models\InvSize;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Models\SampleChallan;
use ME\SflInventory\Services\MerchandisingLink;

/** Delivery Challan — samples (buyer / PP) issued out of a store, against Merchandising PO lines. */
class SampleChallanController extends Controller
{
    public function __construct(private readonly MerchandisingLink $link)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_sample_challan.list');

        $challans = SampleChallan::query()
            ->with('store')
            ->withCount('items')
            ->when($request->filled('search'), fn ($q) => $q->where('challan_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('challan_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('challan_date', '<=', $request->date_to))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sfl-inventory::admin.sample-challans.index', [
            'challans' => $challans,
            'stores' => InvStore::active()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('inv_sample_challan.add');

        // Samples go out of the Accessories (General) store by default.
        $defaultStoreId = InvStore::active()->where('type', InvStore::TYPE_GENERAL)->value('id');
        $challan = new SampleChallan(['challan_date' => today(), 'store_id' => $defaultStoreId]);

        return view('sfl-inventory::admin.sample-challans.create', $this->formData() + compact('challan'));
    }

    public function store(SampleChallanRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $challan = DB::transaction(function () use ($data) {
            $challan = SampleChallan::create([
                'challan_no' => $this->nextChallanNo(),
                'challan_date' => $data['challan_date'],
                'store_id' => $data['store_id'] ?? null,
                'msfl_buyer_id' => $data['msfl_buyer_id'] ?? null,
                'msfl_sample_type_id' => $data['msfl_sample_type_id'] ?? null,
                'sample_type_name' => $this->sampleTypeName($data['msfl_sample_type_id'] ?? null),
                'to_name' => $data['to_name'],
                'to_address' => $data['to_address'] ?? null,
                'to_contact' => $data['to_contact'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'issued_by' => auth()->id(),
            ]);
            $this->syncItems($challan, $data['items']);

            return $challan;
        });

        return redirect()->route('inventory.sample-challans.show', $challan)->with('success', "Challan {$challan->challan_no} created successfully.");
    }

    public function show(SampleChallan $sampleChallan): View
    {
        $this->authorize('inv_sample_challan.view');

        $sampleChallan->load(['store', 'issuer', 'items.size']);

        return view('sfl-inventory::admin.sample-challans.show', ['challan' => $sampleChallan]);
    }

    public function edit(SampleChallan $sampleChallan): View
    {
        $this->authorize('inv_sample_challan.edit');

        $sampleChallan->load('items');

        return view('sfl-inventory::admin.sample-challans.edit', $this->formData() + ['challan' => $sampleChallan]);
    }

    public function update(SampleChallanRequest $request, SampleChallan $sampleChallan): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($sampleChallan, $data) {
            $sampleChallan->update([
                'challan_date' => $data['challan_date'],
                'store_id' => $data['store_id'] ?? null,
                'msfl_buyer_id' => $data['msfl_buyer_id'] ?? null,
                'msfl_sample_type_id' => $data['msfl_sample_type_id'] ?? null,
                'sample_type_name' => $this->sampleTypeName($data['msfl_sample_type_id'] ?? null),
                'to_name' => $data['to_name'],
                'to_address' => $data['to_address'] ?? null,
                'to_contact' => $data['to_contact'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ]);
            $this->syncItems($sampleChallan, $data['items']);
        });

        return redirect()->route('inventory.sample-challans.show', $sampleChallan)->with('success', 'Challan updated successfully.');
    }

    public function destroy(SampleChallan $sampleChallan): RedirectResponse
    {
        $this->authorize('inv_sample_challan.delete');

        $sampleChallan->delete();

        return redirect()->route('inventory.sample-challans.index')->with('success', 'Challan deleted successfully.');
    }

    /** The printed delivery challan. */
    public function print(SampleChallan $sampleChallan): View
    {
        $this->authorize('inv_sample_challan.view');

        $sampleChallan->load(['store', 'items.size']);

        return view('sfl-inventory::admin.sample-challans.print', ['challan' => $sampleChallan]);
    }

    private function nextChallanNo(): string
    {
        $prefix = 'SCH-' . now()->format('Y') . '-';
        $last = SampleChallan::withTrashed()->where('challan_no', 'like', $prefix . '%')
            ->orderByDesc('challan_no')->lockForUpdate()->value('challan_no');
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /** Snapshots item/PO/style/colour from the picked Merchandising PO onto each line. */
    private function syncItems(SampleChallan $challan, array $lines): void
    {
        $poIds = collect($lines)->pluck('msfl_order_po_id')->filter()->unique()->values();
        $pos = $this->link->available()
            ? \ME\MerchandisingSfl\Models\OrderPo::query()->with(['style', 'color'])->whereIn('id', $poIds)->get()->keyBy('id')
            : collect();

        $challan->items()->delete();

        foreach (array_values($lines) as $index => $line) {
            $po = $pos->get($line['msfl_order_po_id']);

            $challan->items()->create([
                'sequence' => ($index + 1) * 10,
                'msfl_order_po_id' => $line['msfl_order_po_id'],
                'item_name' => $po?->style?->name,
                'po_no' => $po?->po_no,
                'style_no' => $po?->style?->style_no,
                'color_name' => $po?->color?->name,
                'size_id' => $line['size_id'] ?? null,
                'buyer_sample_qty' => $line['buyer_sample_qty'] ?? 0,
                'pp_sample_qty' => $line['pp_sample_qty'] ?? 0,
                'unit' => $line['unit'] ?: 'Pcs',
                'remarks' => $line['remarks'] ?? null,
            ]);
        }
    }

    /** Snapshots the picked sample type's code ("PP", "FIT", …) onto the challan. */
    private function sampleTypeName(?int $sampleTypeId): ?string
    {
        return $sampleTypeId ? $this->link->sampleTypes()->firstWhere('id', $sampleTypeId)?->code : null;
    }

    private function formData(): array
    {
        return [
            'stores' => InvStore::active()->orderBy('name')->get(),
            'merBuyersOptions' => $this->link->available() ? $this->link->buyers() : collect(),
            'orderPoOptions' => $this->link->available() ? $this->link->shipmentPos() : collect(),
            'sampleTypeOptions' => $this->link->sampleTypes(),
            'sizes' => InvSize::active()->ordered()->get(),
        ];
    }
}
