<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InvPurchaseRequisitionApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->input('decision') === 'reject' ? 'inv_purchase_requisition.reject' : 'inv_purchase_requisition.approve';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'decision'                 => ['required', 'in:approve,reject'],
            'approval_remarks'         => ['nullable', 'string'],
            // A line either references an existing requested item (has
            // 'id') or is a brand-new item the approver is adding on the
            // spot ('id' absent, 'item_id' present instead) — the "Add"
            // half of the diagram's "Admin Approval (Add/Remove/Edit
            // Option)". A line dropped from the submission entirely (the
            // "Remove" half) simply isn't in $items at all. See
            // withValidator() for the "one of id/item_id" check — kept out
            // of these rules since required_without's wildcard-to-wildcard
            // sibling matching isn't reliable enough to trust here.
            'items'                    => ['required_if:decision,approve', 'array'],
            'items.*.id'               => ['nullable', 'integer', 'exists:inv_purchase_requisition_items,id'],
            'items.*.item_id'          => ['nullable', 'integer', 'exists:inv_items,id'],
            'items.*.color_id'         => ['nullable', 'integer', 'exists:inv_colors,id'],
            'items.*.size_id'          => ['nullable', 'integer', 'exists:inv_sizes,id'],
            'items.*.approved_qty'     => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            foreach ($this->input('items', []) as $index => $line) {
                if (empty($line['id']) && empty($line['item_id'])) {
                    $v->errors()->add("items.{$index}.item_id", 'Select an item for this new line, or remove the row.');
                }
            }
        });
    }
}
