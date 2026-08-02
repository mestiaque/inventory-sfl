<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('purchase_order') ? 'inv_purchase_order.edit' : 'inv_purchase_order.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'supplier_id'          => ['required', 'integer', 'exists:inv_suppliers,id'],
            'order_date'           => ['required', 'date'],
            'expected_date'        => ['nullable', 'date', 'after_or_equal:order_date'],
            'remarks'              => ['nullable', 'string'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.item_id'      => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.0001'],
            'items.*.rate'         => ['required', 'numeric', 'min:0'],
        ];
    }
}
