<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('requisition') ? 'inv_requisition.edit' : 'inv_requisition.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'department_id'          => ['required', 'integer', 'exists:inv_departments,id'],
            'requisition_for'        => ['nullable', 'in:fabrics,accessories,machine_parts,equipment,stationery'],
            'store_id'               => ['required', 'integer', 'exists:inv_stores,id'],
            'received_by'            => ['nullable', 'integer', 'exists:hr_employees,id'],
            'buyer_id'               => ['nullable', 'integer', 'exists:inv_buyers,id'],
            'style'                  => ['nullable', 'string', 'max:150'],
            'order_ref'              => ['nullable', 'string', 'max:150'],
            'requisition_date'       => ['required', 'date'],
            'remarks'                => ['nullable', 'string'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.item_id'        => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.requested_qty'  => ['required', 'numeric', 'min:0.0001'],
        ];
    }
}
