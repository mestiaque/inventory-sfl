<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvProductionConsumptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_production.add');
    }

    public function rules(): array
    {
        return [
            'department_id'          => ['required', 'integer', 'exists:inv_departments,id'],
            'store_id'               => ['required', 'integer', 'exists:inv_stores,id'],
            'issue_id'               => ['nullable', 'integer', 'exists:inv_issues,id'],
            'style'                  => ['nullable', 'string', 'max:150'],
            'order_ref'              => ['nullable', 'string', 'max:150'],
            'consumption_date'       => ['required', 'date'],
            'remarks'                => ['nullable', 'string'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.item_id'        => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.consumed_qty'   => ['required', 'numeric', 'min:0'],
            'items.*.waste_qty'      => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
