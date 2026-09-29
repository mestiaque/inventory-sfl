<?php

namespace ME\SflInventory\Approvals;

use App\Approvals\BaseApprovalHandler;
use App\Models\Approval;
use Illuminate\Database\Eloquent\Model;
use ME\SflInventory\Approvals\Concerns\ResolvesConfiguredRecipients;
use ME\SflInventory\Models\InvGrn;
use ME\SflInventory\Models\InvPurchaseOrderItem;
use ME\SflInventory\Services\StockService;

/**
 * Registered in erp-suhana's config/approval.php under 'inventory.grn_receive'.
 *
 * Mirrors InvPurchaseRequisitionApprovalHandler: a purchase-sourced GRN has
 * its own dedicated approval page (see InvGrnController::approvalForm/
 * approval), which remains the primary way to act on it. This handler only
 * covers who gets emailed when a GRN is submitted for Receive Approval (see
 * src/Config/mail.php to override who that is), and what happens if someone
 * uses the central Approvals list's quick Approve/Reject buttons instead.
 */
class InvGrnApprovalHandler extends BaseApprovalHandler
{
    use ResolvesConfiguredRecipients;

    public function recipients(?Model $approvable, Approval $approval): array
    {
        return $this->resolveRecipients('inventory.grn_receive', 'inv_grn.approve');
    }

    public function onApproved(Approval $approval): void
    {
        $grn = $approval->approvable;

        if (!$grn instanceof InvGrn || $grn->status !== 'pending') {
            return;
        }

        $stock = app(StockService::class);
        $grn->loadMissing('items');

        foreach ($grn->items as $grnItem) {
            $stock->post([
                'item_id'          => $grnItem->item_id,
                'color_id'         => $grnItem->color_id,
                'size_id'          => $grnItem->size_id,
                'store_id'         => $grn->store_id,
                'transaction_date' => $grn->receive_date,
                'transaction_type' => 'grn',
                'qty_in'           => $grnItem->received_qty,
                'rate'             => $grnItem->rate,
                'reference_type'   => 'inv_grn',
                'reference_id'     => $grn->id,
                'remarks'          => "GRN {$grn->grn_number} (approved)",
                'created_by'       => $grn->created_by,
            ]);
        }

        $grn->update([
            'status'           => 'posted',
            'approved_by'      => $approval->approved_by,
            'approved_at'      => $approval->approved_at,
            'approval_remarks' => $approval->remarks,
        ]);
    }

    public function onRejected(Approval $approval): void
    {
        $grn = $approval->approvable;

        if (!$grn instanceof InvGrn || $grn->status !== 'pending') {
            return;
        }

        $grn->loadMissing('items');

        foreach ($grn->items as $grnItem) {
            if ($grnItem->purchase_order_item_id) {
                InvPurchaseOrderItem::find($grnItem->purchase_order_item_id)?->decrement('received_qty', $grnItem->received_qty);
            }
        }

        $grn->purchaseOrder?->refreshReceiptStatus();

        $grn->update([
            'status'           => 'rejected',
            'approved_by'      => $approval->approved_by,
            'approved_at'      => $approval->approved_at,
            'approval_remarks' => $approval->remarks,
        ]);
    }

    public function mailContent(?Model $approvable, Approval $approval): array
    {
        if (! $approvable instanceof InvGrn) {
            return [];
        }

        $approvable->loadMissing(['store', 'supplier', 'purchaseOrder', 'creator', 'receiver', 'items.item.unit', 'items.color', 'items.size', 'items.purchaseOrderItem']);

        return [
            'badge'   => 'GOODS RECEIVE (GRN)',
            'number'  => $approvable->grn_number,
            'date'    => $approvable->receive_date?->format('d.m.Y'),
            'meta'    => [
                'Supplier'        => $approvable->supplier?->name,
                'Store Order'     => $approvable->purchaseOrder?->po_number,
                'Store'           => $approvable->store?->name,
                'Challan/Invoice' => $approvable->challan_invoice_no,
                'Received by'     => $approvable->receiver?->name ?? $approvable->creator?->name,
            ],
            'columns' => [['label' => 'Item'], ['label' => 'Qty', 'align' => 'right'], ['label' => 'UOM'], ['label' => 'Est. Rate', 'align' => 'right'], ['label' => 'Actual Rate', 'align' => 'right'], ['label' => 'Amount (Tk)', 'align' => 'right']],
            'rows'    => $approvable->items->map(fn ($line) => [
                (fn ($line) => ['text' => $line->item?->item_name ?? '-', 'sub' => collect([$line->item?->item_code, $line->color?->name, $line->size?->name])->filter()->implode(' · ')])($line),
                inv_qty($line->received_qty),
                $line->item?->unit?->short_name ?: '-',
                (float) $line->purchaseOrderItem?->rate > 0 ? number_format((float) $line->purchaseOrderItem->rate, 2) : '-',
                number_format((float) $line->rate, 2),
                number_format((float) $line->received_qty * (float) $line->rate, 2),
            ])->all(),
            'total'   => ['label' => 'Total', 'value' => (float) $approvable->items->sum(fn ($line) => (float) $line->received_qty * (float) $line->rate), 'money' => true],
            'notes'   => ['Remarks' => $approvable->remarks],
        ];
    }
}
