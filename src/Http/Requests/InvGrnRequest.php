<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvGrnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_grn.add');
    }

    public function rules(): array
    {
        return [
            'purchase_order_id'             => ['nullable', 'integer', 'exists:inv_purchase_orders,id'],
            'source_type'                   => ['required', 'in:purchase,buyer_supplied'],
            'store_id'                      => ['required', 'integer', 'exists:inv_stores,id'],
            'supplier_id'                   => ['required_if:source_type,purchase', 'nullable', 'integer', 'exists:inv_suppliers,id'],
            'buyer_id'                      => ['required_if:source_type,buyer_supplied', 'nullable', 'integer', 'exists:inv_buyers,id'],
            'style'                         => ['nullable', 'string', 'max:150'],
            'order_ref'                     => ['nullable', 'string', 'max:150'],
            'challan_invoice_no'            => ['nullable', 'string', 'max:100'],
            'receive_date'                  => ['required', 'date'],
            'received_by'                   => ['nullable', 'integer', 'exists:users,id'],
            'remarks'                       => ['nullable', 'string'],
            'items'                         => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['nullable', 'integer', 'exists:inv_purchase_order_items,id'],
            'items.*.item_id'               => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.ordered_qty'           => ['nullable', 'numeric', 'min:0'],
            'items.*.received_qty'          => ['required', 'numeric', 'min:0.0001'],
            'items.*.rejected_qty'          => ['nullable', 'numeric', 'min:0'],
            'items.*.rate'                  => ['required', 'numeric', 'min:0'],
            'items.*.lot_no'                => ['nullable', 'string', 'max:100'],
            'items.*.batch_no'              => ['nullable', 'string', 'max:100'],
        ];
    }
}
