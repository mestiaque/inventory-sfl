<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use ME\SflInventory\Http\Requests\Concerns\ValidatesLineItemVariants;

class InvPurchaseRequisitionRequest extends FormRequest
{
    use ValidatesLineItemVariants;

    public function authorize(): bool
    {
        $ability = $this->route('purchase_requisition') ? 'inv_purchase_requisition.edit' : 'inv_purchase_requisition.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'department_id'          => ['nullable', 'integer', 'exists:inv_departments,id'],
            'requisition_date'       => ['required', 'date'],
            'remarks'                => ['nullable', 'string'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.item_id'        => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.color_id'       => ['nullable', 'integer', 'exists:inv_colors,id'],
            'items.*.size_id'        => ['nullable', 'integer', 'exists:inv_sizes,id'],
            'items.*.requested_qty'  => ['required', 'numeric', 'min:0.0001'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $v) => $this->validateLineItemVariants($v, $this->input('items', [])));
    }
}
