<?php

namespace ME\SflInventory\Http\Controllers;

use App\Models\Approval;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvRequisitionApprovalRequest;
use ME\SflInventory\Http\Requests\InvRequisitionRequest;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Services\MerchandisingLink;
use ME\SflInventory\Models\InvColor;
use ME\SflInventory\Models\InvDepartment;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvRequisition;
use ME\SflInventory\Models\InvRequisitionItem;
use ME\SflInventory\Models\InvSize;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\InvOperatorScopeService;

class InvRequisitionController extends Controller
{
    public function __construct(private readonly InvOperatorScopeService $operatorScope)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('inv_requisition.list');

        $requisitions = InvRequisition::query()
            ->with(['department', 'store', 'buyer', 'requester', 'approver', 'items.item.unit', 'items.color', 'items.size'])
            ->withExists('issues')
            ->when($request->filled('search'), fn ($q) => $q->where('requisition_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('item_id'), fn ($q) => $q->whereHas('items', fn ($iq) => $iq->where('item_id', $request->item_id)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('requisition_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('requisition_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'requested_by'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $departments = InvDepartment::active()->orderBy('name')->get();
        $buyers = InvBuyer::active()->orderBy('name')->get();
        $items = InvItem::active()->orderBy('item_name')->get();

        $trashedRequisitions = InvRequisition::onlyTrashed()->latest('deleted_at')->get()
            ->map(fn ($requisition) => ['id' => $requisition->id, 'title' => $requisition->requisition_no, 'subtitle' => null, 'deleted_at' => $requisition->deleted_at]);

        return view('sfl-inventory::admin.requisitions.index', compact('requisitions', 'departments', 'buyers', 'items', 'trashedRequisitions'));
    }

    public function create(): View
    {
        $this->authorize('inv_requisition.add');

        return view('sfl-inventory::admin.requisitions.create', $this->formOptions());
    }

    public function store(InvRequisitionRequest $request): RedirectResponse
    {
        $data = $this->applyMerchandisingLink($request, $request->validated());

        $autoApprove = $request->boolean('auto_approve') && auth()->user()->can('inv_requisition.approve');

        $requisition = DB::transaction(function () use ($data, $autoApprove) {
            $requisition = InvRequisition::create([
                'requisition_date' => $data['requisition_date'],
                'department_id'    => $data['department_id'],
                'requisition_for'  => $data['requisition_for'] ?? null,
                'store_id'         => $data['store_id'],
                'buyer_id'         => $data['buyer_id'] ?? null,
                'style'            => $data['style'] ?? null,
                'order_ref'        => $data['order_ref'] ?? null,
                'msfl_style_id'              => $data['msfl_style_id'] ?? null,
                'msfl_order_po_id'  => $data['msfl_order_po_id'] ?? null,
                'msfl_buyer_id'              => $data['msfl_buyer_id'] ?? null,
                'requested_by'     => auth()->id(),
                'received_by'      => $data['received_by'] ?? null,
                'status'           => $autoApprove ? 'approved' : 'pending',
                'remarks'          => $data['remarks'] ?? null,
                'created_by'       => auth()->id(),
                'approved_by'      => $autoApprove ? auth()->id() : null,
                'approved_at'      => $autoApprove ? now() : null,
            ]);

            foreach ($data['items'] as $line) {
                [$colorId, $sizeId] = InvItem::find($line['item_id'])?->resolvedVariant($line['color_id'] ?? null, $line['size_id'] ?? null)
                    ?? [$line['color_id'] ?? null, $line['size_id'] ?? null];

                $requisition->items()->create([
                    'item_id'       => $line['item_id'],
                    'color_id'      => $colorId,
                    'size_id'       => $sizeId,
                    'requested_qty' => $line['requested_qty'],
                    ...($autoApprove ? ['approved_qty' => $line['requested_qty']] : []),
                ]);
            }

            return $requisition;
        });

        if ($autoApprove) {
            return redirect()->route('inventory.requisitions.index')->with('success', "Requisition {$requisition->requisition_no} created and auto-approved.");
        }

        app(ApprovalService::class)->request([
            'module'       => 'inventory.requisition',
            'approvable'   => $requisition,
            'title'        => "Requisition Approval - {$requisition->requisition_no}",
            'description'  => "{$requisition->requester?->name} requested materials from {$requisition->department?->name} / {$requisition->store?->name}.",
            'route_name'   => 'inventory.requisitions.approval-form',
            'route_params' => ['requisition' => $requisition->id],
            'requested_by' => auth()->id(),
        ]);

        return redirect()->route('inventory.requisitions.index')->with('success', "Requisition {$requisition->requisition_no} submitted successfully.");
    }

    public function edit(InvRequisition $requisition): View
    {
        $this->authorize('inv_requisition.edit');

        abort_if($requisition->status !== 'pending', 403, 'Only pending requisitions can be edited.');

        $requisition->load('items.item', 'items.color', 'items.size');

        return view('sfl-inventory::admin.requisitions.edit', ['requisition' => $requisition] + $this->formOptions($requisition->items->pluck('item_id')));
    }

    public function update(InvRequisitionRequest $request, InvRequisition $requisition): RedirectResponse
    {
        abort_if($requisition->status !== 'pending', 403, 'Only pending requisitions can be edited.');

        $data = $this->applyMerchandisingLink($request, $request->validated());

        DB::transaction(function () use ($data, $requisition) {
            $requisition->update([
                'requisition_date' => $data['requisition_date'],
                'department_id'    => $data['department_id'],
                'requisition_for'  => $data['requisition_for'] ?? null,
                'store_id'         => $data['store_id'],
                'buyer_id'         => $data['buyer_id'] ?? null,
                'style'            => $data['style'] ?? null,
                'order_ref'        => $data['order_ref'] ?? null,
                'msfl_style_id'              => $data['msfl_style_id'] ?? null,
                'msfl_order_po_id'  => $data['msfl_order_po_id'] ?? null,
                'msfl_buyer_id'              => $data['msfl_buyer_id'] ?? null,
                'received_by'      => $data['received_by'] ?? null,
                'remarks'          => $data['remarks'] ?? null,
            ]);

            $requisition->items()->delete();
            foreach ($data['items'] as $line) {
                [$colorId, $sizeId] = InvItem::find($line['item_id'])?->resolvedVariant($line['color_id'] ?? null, $line['size_id'] ?? null)
                    ?? [$line['color_id'] ?? null, $line['size_id'] ?? null];

                $requisition->items()->create([
                    'item_id'       => $line['item_id'],
                    'color_id'      => $colorId,
                    'size_id'       => $sizeId,
                    'requested_qty' => $line['requested_qty'],
                ]);
            }
        });

        return redirect()->route('inventory.requisitions.index')->with('success', 'Requisition updated successfully.');
    }

    public function approvalForm(InvRequisition $requisition): View
    {
        $this->authorize('inv_requisition.approve');

        abort_if($requisition->status !== 'pending', 403, 'Only pending requisitions can be approved or rejected.');

        $requisition->load('items.item', 'items.color', 'items.size', 'buyer');

        return view('sfl-inventory::admin.requisitions.approve', compact('requisition'));
    }

    public function approval(InvRequisitionApprovalRequest $request, InvRequisition $requisition): RedirectResponse
    {
        abort_if($requisition->status !== 'pending', 403, 'Only pending requisitions can be approved or rejected.');

        $data = $request->validated();

        if ($data['decision'] === 'reject') {
            $requisition->update([
                'status'            => 'rejected',
                'approved_by'       => auth()->id(),
                'approved_at'       => now(),
                'approval_remarks'  => $data['approval_remarks'] ?? null,
            ]);

            $this->syncCentralApproval($requisition, 'rejected', $data['approval_remarks'] ?? null);

            return redirect()->route('inventory.requisitions.index')->with('success', 'Requisition rejected.');
        }

        DB::transaction(function () use ($data, $requisition) {
            foreach ($data['items'] ?? [] as $line) {
                InvRequisitionItem::where('id', $line['id'])
                    ->where('requisition_id', $requisition->id)
                    ->first()
                    ?->update(['approved_qty' => $line['approved_qty'] ?? 0]);
            }

            $requisition->update([
                'status'            => 'approved',
                'approved_by'       => auth()->id(),
                'approved_at'       => now(),
                'approval_remarks'  => $data['approval_remarks'] ?? null,
            ]);
        });

        $this->syncCentralApproval($requisition, 'approved', $data['approval_remarks'] ?? null);

        return redirect()->route('inventory.requisitions.index')->with('success', "Requisition {$requisition->requisition_no} approved.");
    }

    public function print(InvRequisition $requisition): View
    {
        $this->authorize('inv_requisition.print');

        $requisition->load(['items.item.color', 'items.item.size', 'items.color', 'items.size', 'department', 'buyer', 'requester', 'receiver', 'approver']);

        // Every active department / "Requisition For" option from the masters —
        // plus the requisition's own, even if it's been made inactive since —
        // so the right box is always there to tick.
        $departments = InvDepartment::active()->orWhere('id', $requisition->department_id)->orderBy('name')->get();
        $purposes = \ME\SflInventory\Models\InvRequisitionPurpose::withTrashed()
            ->where(fn ($q) => $q->where('is_active', true)->whereNull('deleted_at'))
            ->when($requisition->requisition_for, fn ($q) => $q->orWhere('code', $requisition->requisition_for))
            ->ordered()->get();
        $designation = $this->designationOf($requisition->requester);

        return view('sfl-inventory::admin.requisitions.print', compact('requisition', 'departments', 'purposes', 'designation'));
    }

    /**
     * Pending/rejected: normal delete permission. Approved but nothing
     * issued yet: needs the special delete_approved permission — deleting it
     * releases the stock it was reserving. Anything with a real issue
     * against it can never be deleted here.
     */
    public function destroy(InvRequisition $requisition): RedirectResponse
    {
        $requisition->load('items');

        if (in_array($requisition->status, ['approved', 'partially_issued', 'issued'], true)) {
            $this->authorize('inv_requisition.delete_approved');

            if (! $requisition->isApprovedButUnissued()) {
                return back()->with('error', "{$requisition->requisition_no} already has stock issued against it and cannot be deleted.");
            }
        } else {
            $this->authorize('inv_requisition.delete');
        }

        if ($requisition->isReferenced()) {
            return back()->with('error', 'This requisition has issues against it and cannot be deleted.');
        }

        $requisition->delete();

        return back()->with('success', "Requisition {$requisition->requisition_no} deleted successfully.");
    }

    public function restore(InvRequisition $requisition): RedirectResponse
    {
        $this->authorize('inv_requisition.delete');

        $requisition->restore();

        return back()->with('success', 'Requisition restored successfully.');
    }

    /**
     * A requisition never posts to the stock ledger itself — only the Issue
     * made against it does — so there's no stock to reverse here. If a real
     * Issue references this requisition, that Issue is kept and just loses
     * its requisition link (nullable FK) rather than being destroyed.
     */
    public function forceDestroy(InvRequisition $requisition): RedirectResponse
    {
        $this->authorize('inv_requisition.force_delete');

        DB::transaction(function () use ($requisition) {
            DB::table('inv_issues')->where('requisition_id', $requisition->id)->update(['requisition_id' => null]);
            DB::table('inv_requisition_items')->where('requisition_id', $requisition->id)->delete();

            $requisition->forceDelete();
        });

        return back()->with('success', 'Requisition permanently deleted.');
    }

    /**
     * The requester's designation: the user's employee_id is the HR
     * employee's employee_id, and the designation comes from that employee.
     * Falls back to the designation set on the user account itself.
     */
    private function designationOf(?\App\Models\User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($user->employee_id && class_exists(\ME\Hr\Models\HrEmployee::class)) {
            $designation = \ME\Hr\Models\HrEmployee::query()
                ->where('employee_id', $user->employee_id)
                ->with('designation')
                ->first()?->designation?->name;

            if ($designation) {
                return $designation;
            }
        }

        return $user->hr_designation_id
            ? DB::table('hr_designations')->where('id', $user->hr_designation_id)->value('name')
            : null;
    }

    /**
     * Mirrors the decision made on this dedicated approval form (with its
     * per-line quantities) back onto the central Approvals record, so it
     * stops showing as pending there too. Updated directly — not through
     * ApprovalService::approve()/reject() — because the business effect
     * (per-line approved_qty, requisition status) was already applied above;
     * going through the service again would re-run the handler's generic
     * full-quantity approval on top of it.
     */
    private function syncCentralApproval(InvRequisition $requisition, string $status, ?string $remarks): void
    {
        Approval::where('module', 'inventory.requisition')
            ->where('approvable_type', InvRequisition::class)
            ->where('approvable_id', $requisition->id)
            ->where('status', 'pending')
            ->update([
                'status'      => $status,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'remarks'     => $remarks,
            ]);
    }

    /**
     * Merchandising-linked requisition: the inventory buyer, style text and
     * order ref are derived from the Merchandising buyer / style / PO picked.
     * The Issue later inherits them, and its "received under this style"
     * check matches the Buyer Store receive that was derived the same way.
     */
    private function applyMerchandisingLink(InvRequisitionRequest $request, array $data): array
    {
        if (! $request->usesMerchandising()) {
            return $data;
        }

        $link = app(MerchandisingLink::class);
        $data['buyer_id'] = ! empty($data['msfl_buyer_id']) ? $link->inventoryBuyerId((int) $data['msfl_buyer_id']) : null;
        // An Inventory-only style ("inv:…", see the request) keeps the style text picked.
        $data['style'] = ! empty($data['msfl_style_id']) ? $link->styleNo((int) $data['msfl_style_id'])
            : (! empty($data['msfl_buyer_id']) ? ($data['style'] ?? null) : null);
        $data['order_ref'] = ! empty($data['msfl_order_po_id']) ? $link->orderRef((int) $data['msfl_order_po_id']) : null;

        return $data;
    }

    private function formOptions(iterable $keepItemIds = []): array
    {
        $employees = class_exists(\ME\Hr\Models\HrEmployee::class)
            ? \ME\Hr\Models\HrEmployee::query()->where('status', 1)->orderBy('name')->get()
            : collect();

        return [
            'departments' => InvDepartment::active()->orderBy('name')->get(),
            'purposes'    => \ME\SflInventory\Models\InvRequisitionPurpose::active()->ordered()->get(),
            // Requisitions only ever draw raw material (Warehouse) or
            // accessories — the Finished Goods store is never a source here.
            'stores'      => InvStore::active()->whereIn('type', ['raw_material', 'accessories'])->orderBy('name')->get(),
            'items'       => InvItem::selectable($keepItemIds)->with('unit')->orderBy('item_name')->get(),
            'buyers'      => InvBuyer::active()->orderBy('name')->get(),
            'colors'      => InvColor::active()->orderBy('name')->get(),
            'sizes'       => InvSize::active()->ordered()->get(),
            'employees'   => $employees,
            // Buyer / Style / PO from Merchandising (see MerchandisingLink).
            'merLinked'                  => config('sfl-inventory.requisition_merchandising_link') && app(MerchandisingLink::class)->available(),
            'merBuyersOptions'           => app(MerchandisingLink::class)->buyers(),
            'merStylesOptions'           => app(MerchandisingLink::class)->styles(),
            'merLegacyStyles'            => app(MerchandisingLink::class)->legacyStyles(),
            'merOrderPosOptions' => app(MerchandisingLink::class)->pos(),
            'merReceivedStyleIds'        => app(MerchandisingLink::class)->available() ? app(MerchandisingLink::class)->receivedStyleIds() : [],
            'styleItemRows'              => $this->styleItemRows(),
            'merBuyerInvIds'             => $this->merBuyerInvIds(),
            'receivedStyles'             => $this->receivedStyles(),
        ];
    }

    /** Buyer + style pairs received (posted GRNs) — the Style dropdown, filtered by the picked buyer. */
    private function receivedStyles(): \Illuminate\Support\Collection
    {
        return DB::table('inv_grns')
            ->whereNull('deleted_at')
            ->where('status', 'posted')
            ->whereNotNull('buyer_id')
            ->whereRaw("TRIM(COALESCE(style, '')) != ''")
            ->distinct()
            ->get(['buyer_id', DB::raw('TRIM(style) as style')])
            ->unique(fn ($r) => $r->buyer_id . '|' . mb_strtolower($r->style))
            ->sortBy('style', SORT_NATURAL)
            ->values();
    }

    /**
     * Which items each buyer + style has received (posted GRNs) — lets the
     * Buyer Store requisition form list only that buyer's / style's items.
     * Same style identity as StockService::styleBalance().
     */
    private function styleItemRows(): array
    {
        return DB::table('inv_grn_items as gi')
            ->join('inv_grns as g', 'g.id', '=', 'gi.grn_id')
            ->whereNull('g.deleted_at')
            ->where('g.status', 'posted')
            ->where(fn ($q) => $q->whereNotNull('g.buyer_id')->orWhereNotNull('g.msfl_style_id')->orWhereRaw("TRIM(COALESCE(g.style, '')) != ''"))
            ->distinct()
            ->get(['g.buyer_id', 'g.msfl_style_id', DB::raw("TRIM(COALESCE(g.style, '')) as style"), 'gi.item_id'])
            ->map(fn ($r) => ['b' => $r->buyer_id ? (int) $r->buyer_id : null, 'm' => $r->msfl_style_id ? (int) $r->msfl_style_id : null, 's' => mb_strtolower($r->style), 'i' => (int) $r->item_id])
            ->all();
    }

    /** Merchandising buyer id => matching inventory buyer id (by name), for the form's item filter. */
    private function merBuyerInvIds(): array
    {
        $link = app(MerchandisingLink::class);
        if (! $link->available()) {
            return [];
        }
        $invByName = InvBuyer::query()->get(['id', 'name'])->mapWithKeys(fn ($b) => [mb_strtolower(trim($b->name)) => $b->id]);

        return $link->buyers()->mapWithKeys(fn ($b) => [$b->id => $invByName[mb_strtolower(trim($b->name))] ?? null])->all();
    }
}
