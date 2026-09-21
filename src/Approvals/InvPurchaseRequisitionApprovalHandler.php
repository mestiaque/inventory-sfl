<?php

namespace ME\SflInventory\Approvals;

use App\Approvals\BaseApprovalHandler;
use App\Models\Approval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ME\SflInventory\Approvals\Concerns\ResolvesConfiguredRecipients;
use ME\SflInventory\Models\InvPurchaseRequisition;
use ME\SflInventory\Models\InvPurchaseRequisitionItem;

/**
 * Registered in erp-suhana's config/approval.php under 'inventory.purchase_requisition'.
 *
 * Mirrors InvRequisitionApprovalHandler: the purchase requisition has its own
 * dedicated approval page (per-line quantity, reject-with-remarks — see
 * InvPurchaseRequisitionController::approvalForm/approval). That page remains
 * the primary way to act on a request; this handler only covers:
 *   - who gets emailed when a purchase requisition is submitted (recipients)
 *     — see src/Config/mail.php to override who that is
 *   - what happens if someone uses the central Approvals list's quick
 *     Approve/Reject buttons instead (onApproved/onRejected) — a full-quantity
 *     approve, since the central button has no per-line quantity input.
 */
class InvPurchaseRequisitionApprovalHandler extends BaseApprovalHandler
{
    use ResolvesConfiguredRecipients;

    public function recipients(?Model $approvable, Approval $approval): array
    {
        return $this->resolveRecipients('inventory.purchase_requisition', 'inv_purchase_requisition.approve');
    }

    public function onApproved(Approval $approval): void
    {
        $purchaseRequisition = $approval->approvable;

        if (!$purchaseRequisition instanceof InvPurchaseRequisition || $purchaseRequisition->status !== 'pending') {
            return;
        }

        InvPurchaseRequisitionItem::where('purchase_requisition_id', $purchaseRequisition->id)
            ->update(['approved_qty' => DB::raw('requested_qty')]);

        $purchaseRequisition->update([
            'status'           => 'approved',
            'approved_by'      => $approval->approved_by,
            'approved_at'      => $approval->approved_at,
            'approval_remarks' => $approval->remarks,
        ]);
    }

    public function onRejected(Approval $approval): void
    {
        $purchaseRequisition = $approval->approvable;

        if (!$purchaseRequisition instanceof InvPurchaseRequisition || $purchaseRequisition->status !== 'pending') {
            return;
        }

        $purchaseRequisition->update([
            'status'           => 'rejected',
            'approved_by'      => $approval->approved_by,
            'approved_at'      => $approval->approved_at,
            'approval_remarks' => $approval->remarks,
        ]);
    }
}
