<?php

namespace ME\SflInventory\Approvals;

use App\Approvals\BaseApprovalHandler;
use App\Models\Approval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ME\SflInventory\Approvals\Concerns\ResolvesConfiguredRecipients;
use ME\SflInventory\Models\InvRequisition;
use ME\SflInventory\Models\InvRequisitionItem;

/**
 * Registered in erp-suhana's config/approval.php under 'inventory.requisition'.
 *
 * Requisitions already have their own dedicated approval page (per-line
 * quantity, reject-with-remarks — see InvRequisitionController::approvalForm/
 * approval). That page remains the primary way to act on a request; this
 * handler only covers:
 *   - who gets emailed when a requisition is submitted (recipients) — see
 *     src/Config/mail.php to override who that is
 *   - what happens if someone uses the central Approvals list's quick
 *     Approve/Reject buttons instead (onApproved/onRejected) — a full-quantity
 *     approve, since the central button has no per-line quantity input.
 */
class InvRequisitionApprovalHandler extends BaseApprovalHandler
{
    use ResolvesConfiguredRecipients;

    public function recipients(?Model $approvable, Approval $approval): array
    {
        return $this->resolveRecipients('inventory.requisition', 'inv_requisition.approve');
    }

    public function onApproved(Approval $approval): void
    {
        $requisition = $approval->approvable;

        if (!$requisition instanceof InvRequisition || $requisition->status !== 'pending') {
            return;
        }

        InvRequisitionItem::where('requisition_id', $requisition->id)
            ->update(['approved_qty' => DB::raw('requested_qty')]);

        $requisition->update([
            'status'           => 'approved',
            'approved_by'      => $approval->approved_by,
            'approved_at'      => $approval->approved_at,
            'approval_remarks' => $approval->remarks,
        ]);
    }

    public function onRejected(Approval $approval): void
    {
        $requisition = $approval->approvable;

        if (!$requisition instanceof InvRequisition || $requisition->status !== 'pending') {
            return;
        }

        $requisition->update([
            'status'           => 'rejected',
            'approved_by'      => $approval->approved_by,
            'approved_at'      => $approval->approved_at,
            'approval_remarks' => $approval->remarks,
        ]);
    }

    public function mailContent(?Model $approvable, Approval $approval): array
    {
        if (! $approvable instanceof InvRequisition) {
            return [];
        }

        $approvable->loadMissing(['department', 'store', 'buyer', 'requester', 'receiver', 'items.item.unit', 'items.color', 'items.size']);
        $stock = app(\ME\SflInventory\Services\StockService::class);

        return [
            'badge'   => 'STORE REQUISITION',
            'number'  => $approvable->requisition_no,
            'date'    => $approvable->requisition_date?->format('d.m.Y'),
            'meta'    => [
                'Requested by' => $approvable->requester?->name,
                'Department'   => $approvable->department?->name,
                'Issue from'   => $approvable->store?->name,
                'Receiver'     => $approvable->receiver?->name,
                'Buyer / Style' => collect([$approvable->buyer?->name, $approvable->style])->filter()->implode(' / '),
            ],
            'columns' => [['label' => 'Item'], ['label' => 'Requested', 'align' => 'right'], ['label' => 'UOM'], ['label' => 'Available in store', 'align' => 'right']],
            'rows'    => $approvable->items->map(fn ($line) => [
                (fn ($line) => ['text' => $line->item?->item_name ?? '-', 'sub' => collect([$line->item?->item_code, $line->color?->name, $line->size?->name])->filter()->implode(' · ')])($line),
                inv_qty($line->requested_qty),
                $line->item?->unit?->short_name ?: '-',
                inv_qty($stock->availableStock($line->item_id, $approvable->store_id, $line->color_id, $line->size_id, $approvable->id)),
            ])->all(),
            'total'   => ['label' => 'Total Requested Qty', 'value' => (float) $approvable->items->sum('requested_qty'), 'money' => false],
            'notes'   => ['Remarks' => $approvable->remarks],
        ];
    }
}
