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
use ME\SflInventory\Models\InvDepartment;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvRequisition;
use ME\SflInventory\Models\InvRequisitionItem;
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
            ->with(['department', 'store', 'buyer', 'requester', 'approver', 'items.item.unit'])
            ->when($request->filled('search'), fn ($q) => $q->where('requisition_no', 'like', '%' . $request->search . '%'))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('buyer_id'), fn ($q) => $q->where('buyer_id', $request->buyer_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('requisition_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('requisition_date', '<=', $request->date_to))
            ->tap(fn ($q) => $this->operatorScope->applyToStore($q, 'store_id', 'requested_by'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $departments = InvDepartment::active()->orderBy('name')->get();
        $buyers = InvBuyer::active()->orderBy('name')->get();

        return view('sfl-inventory::admin.requisitions.index', compact('requisitions', 'departments', 'buyers'));
    }

    public function create(): View
    {
        $this->authorize('inv_requisition.add');

        return view('sfl-inventory::admin.requisitions.create', $this->formOptions());
    }

    public function store(InvRequisitionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $requisition = DB::transaction(function () use ($data) {
            $requisition = InvRequisition::create([
                'requisition_date' => $data['requisition_date'],
                'department_id'    => $data['department_id'],
                'requisition_for'  => $data['requisition_for'] ?? null,
                'store_id'         => $data['store_id'],
                'buyer_id'         => $data['buyer_id'] ?? null,
                'style'            => $data['style'] ?? null,
                'order_ref'        => $data['order_ref'] ?? null,
                'requested_by'     => auth()->id(),
                'received_by'      => $data['received_by'] ?? null,
                'status'           => 'pending',
                'remarks'          => $data['remarks'] ?? null,
                'created_by'       => auth()->id(),
            ]);

            foreach ($data['items'] as $line) {
                $requisition->items()->create([
                    'item_id'       => $line['item_id'],
                    'requested_qty' => $line['requested_qty'],
                ]);
            }

            return $requisition;
        });

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

        $requisition->load('items');

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
                'received_by'      => $data['received_by'] ?? null,
                'remarks'          => $data['remarks'] ?? null,
            ]);

            $requisition->items()->delete();
            foreach ($data['items'] as $line) {
                $requisition->items()->create([
                    'item_id'       => $line['item_id'],
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

        $requisition->load('items.item', 'buyer');

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
                    ->update(['approved_qty' => $line['approved_qty'] ?? 0]);
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

        $requisition->load(['items.item.color', 'items.item.size', 'department', 'buyer', 'requester', 'receiver', 'approver']);

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
            'employees'   => $employees,
        ];
    }
}
