<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_shipment.add');
    }

    public function rules(): array
    {
        return [
            'shipment_date'     => ['required', 'date'],
            'buyer_id'          => ['nullable', 'integer', 'exists:inv_buyers,id'],
            'invoice_no'        => ['nullable', 'string', 'max:100'],
            'packing_list_no'   => ['nullable', 'string', 'max:100'],
            'store_id'          => ['required', 'integer', 'exists:inv_stores,id'],
            'remarks'           => ['nullable', 'string'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.item_id'   => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.quantity'  => ['required', 'numeric', 'min:0.0001'],
        ];
    }
}
