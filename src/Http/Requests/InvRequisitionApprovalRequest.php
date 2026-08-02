<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvRequisitionApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->input('decision') === 'reject' ? 'inv_requisition.reject' : 'inv_requisition.approve';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'decision'                 => ['required', 'in:approve,reject'],
            'approval_remarks'         => ['nullable', 'string'],
            'items'                    => ['required_if:decision,approve', 'array'],
            'items.*.id'               => ['required_with:items', 'integer', 'exists:inv_requisition_items,id'],
            'items.*.approved_qty'     => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
