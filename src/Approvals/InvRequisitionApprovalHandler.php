<?php

namespace ME\SflInventory\Approvals;

use App\Approvals\BaseApprovalHandler;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ME\SflInventory\Models\InvRequisition;
use ME\SflInventory\Models\InvRequisitionItem;

/**
 * Registered in erp-suhana's config/approval.php under 'inventory.requisition'.
 *
 * Requisitions already have their own dedicated approval page (per-line
 * quantity, reject-with-remarks — see InvRequisitionController::approvalForm/
 * approval). That page remains the primary way to act on a request; this
 * handler only covers:
 *   - who gets emailed when a requisition is submitted (recipients)
 *   - what happens if someone uses the central Approvals list's quick
 *     Approve/Reject buttons instead (onApproved/onRejected) — a full-quantity
 *     approve, since the central button has no per-line quantity input.
 */
class InvRequisitionApprovalHandler extends BaseApprovalHandler
{
    public function recipients(?Model $approvable, Approval $approval): array
    {
        return User::all()
            ->filter(fn (User $user) => $user->hasPermission('inv_requisition.approve'))
            ->pluck('email')
            ->filter()
            ->values()
            ->all();
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
}
