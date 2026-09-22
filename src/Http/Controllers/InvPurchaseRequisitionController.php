<?php

namespace ME\SflInventory\Http\Controllers;

use App\Models\Approval;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvPurchaseRequisitionApprovalRequest;
use ME\SflInventory\Http\Requests\InvPurchaseRequisitionRequest;
use ME\SflInventory\Models\InvColor;
use ME\SflInventory\Models\InvDepartment;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvPurchaseOrder;
use ME\SflInventory\Models\InvPurchaseRequisition;
use ME\SflInventory\Models\InvPurchaseRequisitionItem;
use ME\SflInventory\Models\InvSize;

class InvPurchaseRequisitionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_purchase_requisition.list');

        $purchaseRequisitions = InvPurchaseRequisition::query()
            ->with(['department', 'requester', 'approver', 'items.item.unit', 'items.color', 'items.size'])
            ->when($request->filled('search'), fn ($q) => $q->where('requisition_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('requisition_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('requisition_date', '<=', $request->date_to))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $departments = InvDepartment::active()->orderBy('name')->get();
        $items = InvItem::active()->orderBy('item_name')->get();

        $trashedPurchaseRequisitions = InvPurchaseRequisition::onlyTrashed()->latest('deleted_at')->get()
            ->map(fn ($pr) => ['id' => $pr->id, 'title' => $pr->requisition_no, 'subtitle' => null, 'deleted_at' => $pr->deleted_at]);

        return view('sfl-inventory::admin.purchase-requisitions.index', compact('purchaseRequisitions', 'departments', 'items', 'trashedPurchaseRequisitions'));
    }

    public function create(): View
    {
        $this->authorize('inv_purchase_requisition.add');

        return view('sfl-inventory::admin.purchase-requisitions.create', $this->formOptions());
    }

    public function store(InvPurchaseRequisitionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $autoApprove = $request->boolean('auto_approve') && auth()->user()->can('inv_purchase_requisition.approve');

        $purchaseRequisition = DB::transaction(function () use ($data, $autoApprove) {
            $purchaseRequisition = InvPurchaseRequisition::create([
                'requisition_date' => $data['requisition_date'],
                'department_id'    => $data['department_id'] ?? null,
                'requested_by'     => auth()->id(),
                'status'           => $autoApprove ? 'approved' : 'pending',
                'remarks'          => $data['remarks'] ?? null,
                'created_by'       => auth()->id(),
                'approved_by'      => $autoApprove ? auth()->id() : null,
                'approved_at'      => $autoApprove ? now() : null,
            ]);

            foreach ($data['items'] as $line) {
                [$colorId, $sizeId] = InvItem::find($line['item_id'])?->resolvedVariant($line['color_id'] ?? null, $line['size_id'] ?? null)
                    ?? [$line['color_id'] ?? null, $line['size_id'] ?? null];

                $purchaseRequisition->items()->create([
                    'item_id'       => $line['item_id'],
                    'color_id'      => $colorId,
                    'size_id'       => $sizeId,
                    'requested_qty' => $line['requested_qty'],
                    ...($autoApprove ? ['approved_qty' => $line['requested_qty']] : []),
                ]);
            }

            return $purchaseRequisition;
        });

        if ($autoApprove) {
            $this->autoCreatePurchaseOrder($purchaseRequisition);

            return redirect()->route('inventory.purchase-requisitions.index')->with('success', "Purchase requisition {$purchaseRequisition->requisition_no} created, auto-approved, and its Store Order was created automatically.");
        }

        app(ApprovalService::class)->request([
            'module'       => 'inventory.purchase_requisition',
            'approvable'   => $purchaseRequisition,
            'title'        => "Purchase Requisition Approval - {$purchaseRequisition->requisition_no}",
            'description'  => "{$purchaseRequisition->requester?->name} requested a purchase" . ($purchaseRequisition->department ? " for {$purchaseRequisition->department->name}" : '') . '.',
            'route_name'   => 'inventory.purchase-requisitions.approval-form',
            'route_params' => ['purchase_requisition' => $purchaseRequisition->id],
            'requested_by' => auth()->id(),
        ]);

        return redirect()->route('inventory.purchase-requisitions.index')->with('success', "Purchase requisition {$purchaseRequisition->requisition_no} submitted successfully.");
    }

    public function edit(InvPurchaseRequisition $purchase_requisition): View
    {
        $this->authorize('inv_purchase_requisition.edit');

        abort_if($purchase_requisition->status !== 'pending', 403, 'Only pending purchase requisitions can be edited.');

        $purchase_requisition->load('items.item', 'items.color', 'items.size');

        return view('sfl-inventory::admin.purchase-requisitions.edit', ['purchaseRequisition' => $purchase_requisition] + $this->formOptions());
    }

    public function update(InvPurchaseRequisitionRequest $request, InvPurchaseRequisition $purchase_requisition): RedirectResponse
    {
        abort_if($purchase_requisition->status !== 'pending', 403, 'Only pending purchase requisitions can be edited.');

        $data = $request->validated();

        DB::transaction(function () use ($data, $purchase_requisition) {
            $purchase_requisition->update([
                'requisition_date' => $data['requisition_date'],
                'department_id'    => $data['department_id'] ?? null,
                'remarks'          => $data['remarks'] ?? null,
            ]);

            $purchase_requisition->items()->delete();
            foreach ($data['items'] as $line) {
                [$colorId, $sizeId] = InvItem::find($line['item_id'])?->resolvedVariant($line['color_id'] ?? null, $line['size_id'] ?? null)
                    ?? [$line['color_id'] ?? null, $line['size_id'] ?? null];

                $purchase_requisition->items()->create([
                    'item_id'       => $line['item_id'],
                    'color_id'      => $colorId,
                    'size_id'       => $sizeId,
                    'requested_qty' => $line['requested_qty'],
                ]);
            }
        });

        return redirect()->route('inventory.purchase-requisitions.index')->with('success', 'Purchase requisition updated successfully.');
    }

    public function approvalForm(InvPurchaseRequisition $purchase_requisition): View
    {
        $this->authorize('inv_purchase_requisition.approve');

        abort_if($purchase_requisition->status !== 'pending', 403, 'Only pending purchase requisitions can be approved or rejected.');

        $purchase_requisition->load('items.item', 'items.color', 'items.size', 'department');

        return view('sfl-inventory::admin.purchase-requisitions.approve', ['purchaseRequisition' => $purchase_requisition] + $this->formOptions());
    }

    public function approval(InvPurchaseRequisitionApprovalRequest $request, InvPurchaseRequisition $purchase_requisition): RedirectResponse
    {
        abort_if($purchase_requisition->status !== 'pending', 403, 'Only pending purchase requisitions can be approved or rejected.');

        $data = $request->validated();

        if ($data['decision'] === 'reject') {
            $purchase_requisition->update([
                'status'           => 'rejected',
                'approved_by'      => auth()->id(),
                'approved_at'      => now(),
                'approval_remarks' => $data['approval_remarks'] ?? null,
            ]);

            $this->syncCentralApproval($purchase_requisition, 'rejected', $data['approval_remarks'] ?? null);

            return redirect()->route('inventory.purchase-requisitions.index')->with('success', 'Purchase requisition rejected.');
        }

        DB::transaction(function () use ($data, $purchase_requisition) {
            foreach ($data['items'] ?? [] as $line) {
                if (! empty($line['id'])) {
                    // Edit: an existing requested line — adjust its approved
                    // qty (and, if the approver corrected it, its variant).
                    // Any line the requester asked for that's simply missing
                    // from this submission (the approver deleted its row in
                    // the UI) is the "Remove" case — it keeps its default
                    // approved_qty of 0 and is never converted.
                    $item = InvPurchaseRequisitionItem::where('id', $line['id'])
                        ->where('purchase_requisition_id', $purchase_requisition->id)
                        ->first();

                    [$colorId, $sizeId] = $item?->item?->resolvedVariant($line['color_id'] ?? null, $line['size_id'] ?? null)
                        ?? [$line['color_id'] ?? null, $line['size_id'] ?? null];

                    $item?->update([
                        'approved_qty' => $line['approved_qty'] ?? 0,
                        'color_id'     => $colorId,
                        'size_id'      => $sizeId,
                    ]);
                } elseif (! empty($line['item_id']) && (float) ($line['approved_qty'] ?? 0) > 0) {
                    // Add: a brand-new item the approver introduced during
                    // approval, never originally requested — requested_qty
                    // mirrors what's being approved since there's no
                    // original ask to compare against.
                    [$colorId, $sizeId] = InvItem::find($line['item_id'])?->resolvedVariant($line['color_id'] ?? null, $line['size_id'] ?? null)
                        ?? [$line['color_id'] ?? null, $line['size_id'] ?? null];

                    $purchase_requisition->items()->create([
                        'item_id'       => $line['item_id'],
                        'color_id'      => $colorId,
                        'size_id'       => $sizeId,
                        'requested_qty' => $line['approved_qty'],
                        'approved_qty'  => $line['approved_qty'],
                    ]);
                }
            }

            $purchase_requisition->update([
                'status'           => 'approved',
                'approved_by'      => auth()->id(),
                'approved_at'      => now(),
                'approval_remarks' => $data['approval_remarks'] ?? null,
            ]);
        });

        $this->syncCentralApproval($purchase_requisition, 'approved', $data['approval_remarks'] ?? null);

        $this->autoCreatePurchaseOrder($purchase_requisition);

        return redirect()->route('inventory.purchase-requisitions.index')->with('success', "Purchase requisition {$purchase_requisition->requisition_no} approved and its Store Order was created automatically.");
    }

    public function destroy(InvPurchaseRequisition $purchase_requisition): RedirectResponse
    {
        $this->authorize('inv_purchase_requisition.delete');

        if ($purchase_requisition->isReferenced()) {
            return back()->with('error', 'This purchase requisition has purchase orders against it and cannot be deleted.');
        }

        $purchase_requisition->delete();

        return back()->with('success', 'Purchase requisition deleted successfully.');
    }

    public function restore(InvPurchaseRequisition $purchase_requisition): RedirectResponse
    {
        $this->authorize('inv_purchase_requisition.delete');

        $purchase_requisition->restore();

        return back()->with('success', 'Purchase requisition restored successfully.');
    }

    /**
     * A purchase requisition never posts to the stock ledger itself — only
     * the Purchase Order (and its GRNs) made against it do — so there's no
     * stock to reverse here. If a real Purchase Order references this
     * requisition, that order is kept and just loses its requisition link
     * (nullable FK) rather than being destroyed.
     */
    public function forceDestroy(InvPurchaseRequisition $purchase_requisition): RedirectResponse
    {
        $this->authorize('inv_purchase_requisition.force_delete');

        DB::transaction(function () use ($purchase_requisition) {
            DB::table('inv_purchase_orders')->where('purchase_requisition_id', $purchase_requisition->id)->update(['purchase_requisition_id' => null]);
            DB::table('inv_purchase_requisition_items')->where('purchase_requisition_id', $purchase_requisition->id)->delete();

            $purchase_requisition->forceDelete();
        });

        return back()->with('success', 'Purchase requisition permanently deleted.');
    }

    /**
     * A Store Order is no longer created by hand — the moment a requisition
     * is approved (fully or partially, e.g. 100 requested but only 80
     * approved), one Store Order is auto-created here covering exactly the
     * approved lines/quantities. Supplier and rate aren't known yet at this
     * point — those are picked/entered later, at GRN Receive time (see
     * InvGrnController::store(), which now also backfills this Store
     * Order's supplier_id from the receiving GRN). A line approved with 0
     * qty (effectively rejected even though the requisition itself is
     * 'approved') is simply excluded; if every line is like that, no Store
     * Order is created at all.
     */
    private function autoCreatePurchaseOrder(InvPurchaseRequisition $purchaseRequisition): void
    {
        $purchaseRequisition->loadMissing('items');

        $approvedLines = $purchaseRequisition->items->filter(fn (InvPurchaseRequisitionItem $item) => $item->approved_qty > 0);

        if ($approvedLines->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($purchaseRequisition, $approvedLines) {
            $purchaseOrder = InvPurchaseOrder::create([
                'purchase_requisition_id' => $purchaseRequisition->id,
                'supplier_id'   => null,
                'order_date'    => now()->toDateString(),
                'expected_date' => null,
                'status'        => 'approved',
                'total_amount'  => 0,
                'remarks'       => null,
                'created_by'    => auth()->id(),
            ]);

            foreach ($approvedLines as $line) {
                $purchaseOrder->items()->create([
                    'item_id'  => $line->item_id,
                    'color_id' => $line->color_id,
                    'size_id'  => $line->size_id,
                    'quantity' => $line->approved_qty,
                    'rate'     => 0,
                    'amount'   => 0,
                ]);

                $line->increment('converted_qty', $line->approved_qty);
            }
        });

        $purchaseRequisition->refreshConversionStatus();
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
    private function syncCentralApproval(InvPurchaseRequisition $purchase_requisition, string $status, ?string $remarks): void
    {
        Approval::where('module', 'inventory.purchase_requisition')
            ->where('approvable_type', InvPurchaseRequisition::class)
            ->where('approvable_id', $purchase_requisition->id)
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
        return [
            'departments' => InvDepartment::active()->orderBy('name')->get(),
            'items'       => InvItem::active()->with('unit')->orderBy('item_name')->get(),
            'colors'      => InvColor::active()->orderBy('name')->get(),
            'sizes'       => InvSize::active()->ordered()->get(),
        ];
    }
}
