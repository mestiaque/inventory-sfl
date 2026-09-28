<?php

namespace ME\SflInventory\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use ME\SflInventory\Models\InvPurchaseRequisition;

/**
 * Detailed "Purchase Requisition — Approval Required" memo email, sent right
 * after a purchase requisition is submitted — same concept as the Accounts
 * Expense approval (ME\AccSfl\Services\ExpenseApprovalMailService): every
 * line with its estimated rate/amount, the estimated total in figures and
 * words, and a Review & Approve link.
 *
 * Recipients: every user holding inv_purchase_requisition.approve with a
 * valid email — unless src/Config/mail.php (or the host's
 * config/sfl-inventory-mail.php) lists addresses for
 * 'inventory.purchase_requisition', which then take over completely.
 */
class PurchaseRequisitionApprovalMailService
{
    public function send(InvPurchaseRequisition $purchaseRequisition): void
    {
        $recipients = $this->recipients();

        if ($recipients === []) {
            Log::warning('Purchase requisition approval email was skipped because no user with inv_purchase_requisition.approve permission has a valid email.', [
                'purchase_requisition_id' => $purchaseRequisition->getKey(),
            ]);

            return;
        }

        $purchaseRequisition->loadMissing(['department', 'requester', 'items.item.unit', 'items.color', 'items.size']);
        $total = $purchaseRequisition->estimated_total;

        try {
            Mail::send('sfl-inventory::emails.purchase-requisition-approval', [
                'purchaseRequisition' => $purchaseRequisition,
                'estimatedTotal'      => $total,
                'amountInWords'       => $this->inWords($total),
                'approvalUrl'         => route('inventory.purchase-requisitions.approval-form', $purchaseRequisition),
            ], function ($message) use ($recipients, $purchaseRequisition, $total): void {
                $message->to($recipients)
                    ->subject("Purchase Requisition {$purchaseRequisition->requisition_no} - Est. Tk " . number_format($total, 2) . ' - Approval Required');
            });
        } catch (\Throwable $exception) {
            Log::error('Purchase requisition approval email could not be sent.', [
                'purchase_requisition_id' => $purchaseRequisition->getKey(),
                'exception'               => $exception->getMessage(),
            ]);
        }
    }

    /** @return list<string> */
    public function recipients(): array
    {
        // Module keys contain a dot, so read the array directly — config()'s
        // dot-notation would treat it as a nested path and never find it.
        $configured = array_values(array_filter(
            (array) (config('sfl-inventory-mail.approval_recipients', [])['inventory.purchase_requisition'] ?? [])
        ));

        $emails = $configured ?: User::all()
            ->filter(fn (User $user) => $user->hasPermission('inv_purchase_requisition.approve'))
            ->pluck('email')
            ->all();

        return collect($emails)
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }

    /** Taka in words via the Accounts package's converter, when it's installed. */
    private function inWords(float $amount): ?string
    {
        $converter = 'ME\\AccSfl\\Services\\NumberToWordsService';

        return class_exists($converter) ? app($converter)->taka($amount) : null;
    }
}
