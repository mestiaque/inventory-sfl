<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_issue.add');
    }

    public function rules(): array
    {
        return [
            'requisition_id'              => ['nullable', 'integer', 'exists:inv_requisitions,id'],
            'store_id'                    => ['required', 'integer', 'exists:inv_stores,id'],
            'to_store_id'                 => ['nullable', 'integer', 'different:store_id', 'exists:inv_stores,id'],
            'department_id'               => ['required', 'integer', 'exists:inv_departments,id'],
            'buyer_id'                    => ['nullable', 'integer', 'exists:inv_buyers,id'],
            'style'                       => ['nullable', 'string', 'max:150'],
            'order_ref'                   => ['nullable', 'string', 'max:150'],
            'issue_date'                  => ['required', 'date'],
            'remarks'                     => ['nullable', 'string'],
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.requisition_item_id' => ['nullable', 'integer', 'exists:inv_requisition_items,id'],
            'items.*.item_id'             => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.issued_qty'          => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_rate'           => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
