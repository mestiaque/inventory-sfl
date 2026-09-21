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
            // Required when creating (every Purchase Order must come from an
            // approved Purchase Requisition) but not on update — the edit
            // form never re-submits this, and the link is fixed at creation.
            'purchase_requisition_id'              => [
                $this->route('purchase_order') ? 'nullable' : 'required',
                'integer',
                'exists:inv_purchase_requisitions,id',
            ],
            'supplier_id'                           => ['required', 'integer', 'exists:inv_suppliers,id'],
            'order_date'                            => ['required', 'date'],
            'expected_date'                         => ['nullable', 'date', 'after_or_equal:order_date'],
            'remarks'                               => ['nullable', 'string'],
            'items'                                 => ['required', 'array', 'min:1'],
            'items.*.item_id'                       => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.purchase_requisition_item_id'  => ['nullable', 'integer', 'exists:inv_purchase_requisition_items,id'],
            'items.*.quantity'                       => ['required', 'numeric', 'min:0.0001'],
            // Price is captured at Store Receive time now, not here — see
            // InvGrnRequest/create-purchase.blade.php. A Store Order only
            // fixes what and how much, never at what price.
            'items.*.rate'                           => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
