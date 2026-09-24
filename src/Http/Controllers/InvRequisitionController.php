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
        $data = $request->validated();

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
                'mer_style_id'              => $data['mer_style_id'] ?? null,
                'mer_sales_contract_po_id'  => $data['mer_sales_contract_po_id'] ?? null,
                'mer_buyer_id'              => $data['mer_buyer_id'] ?? null,
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

        return view('sfl-inventory::admin.requisitions.edit', ['requisition' => $requisition] + $this->formOptions());
    }

    public function update(InvRequisitionRequest $request, InvRequisition $requisition): RedirectResponse
    {
        abort_if($requisition->status !== 'pending', 403, 'Only pending requisitions can be edited.');

        $data = $request->validated();

        DB::transaction(function () use ($data, $requisition) {
            $requisition->update([
                'requisition_date' => $data['requisition_date'],
                'department_id'    => $data['department_id'],
                'requisition_for'  => $data['requisition_for'] ?? null,
                'store_id'         => $data['store_id'],
                'buyer_id'         => $data['buyer_id'] ?? null,
                'style'            => $data['style'] ?? null,
                'order_ref'        => $data['order_ref'] ?? null,
                'mer_style_id'              => $data['mer_style_id'] ?? null,
                'mer_sales_contract_po_id'  => $data['mer_sales_contract_po_id'] ?? null,
                'mer_buyer_id'              => $data['mer_buyer_id'] ?? null,
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

        $departments = InvDepartment::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.requisitions.print', compact('requisition', 'departments'));
    }

    public function destroy(InvRequisition $requisition): RedirectResponse
    {
        $this->authorize('inv_requisition.delete');

        if ($requisition->isReferenced()) {
            return back()->with('error', 'This requisition has issues against it and cannot be deleted.');
        }

        $requisition->delete();

        return back()->with('success', 'Requisition deleted successfully.');
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

    private function formOptions(): array
    {
        $employees = class_exists(\ME\Hr\Models\HrEmployee::class)
            ? \ME\Hr\Models\HrEmployee::query()->where('status', 1)->orderBy('name')->get()
            : collect();

        return [
            'departments' => InvDepartment::active()->orderBy('name')->get(),
            // Requisitions only ever draw raw material (Warehouse) or
            // accessories — the Finished Goods store is never a source here.
            'stores'      => InvStore::active()->whereIn('type', ['raw_material', 'accessories'])->orderBy('name')->get(),
            'items'       => InvItem::active()->with('unit')->orderBy('item_name')->get(),
            'buyers'      => InvBuyer::active()->orderBy('name')->get(),
            'colors'      => InvColor::active()->orderBy('name')->get(),
            'sizes'       => InvSize::active()->ordered()->get(),
            'employees'   => $employees,
            'merStylesOptions' => class_exists(\ME\MerchandisingTrace\Models\Style::class)
                ? \ME\MerchandisingTrace\Models\Style::query()->orderBy('style_no')->get(['id', 'style_no', 'name'])
                : collect(),
            'merSalesContractPosOptions' => class_exists(\ME\MerchandisingTrace\Models\SalesContractPo::class)
                ? \ME\MerchandisingTrace\Models\SalesContractPo::query()->latest('id')->limit(500)->get(['id', 'po_no'])
                : collect(),
            'merBuyersOptions' => class_exists(\ME\MerchandisingTrace\Models\Buyer::class)
                ? \ME\MerchandisingTrace\Models\Buyer::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ];
    }
}
