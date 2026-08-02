<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvFinishedGoodsReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_fg_receive.add');
    }

    public function rules(): array
    {
        return [
            'receive_date'      => ['required', 'date'],
            'style'             => ['nullable', 'string', 'max:150'],
            'buyer_id'          => ['nullable', 'integer', 'exists:inv_buyers,id'],
            'order_ref'         => ['nullable', 'string', 'max:150'],
            'store_id'          => ['required', 'integer', 'exists:inv_stores,id'],
            'remarks'           => ['nullable', 'string'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.item_id'   => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.quantity'  => ['required', 'numeric', 'min:0.0001'],
        ];
    }
}
