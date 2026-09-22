<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('transfer') ? 'inv_transfer.edit' : 'inv_transfer.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'from_store_id'      => ['required', 'integer', 'exists:inv_stores,id'],
            'to_store_id'        => ['required', 'integer', 'different:from_store_id', 'exists:inv_stores,id'],
            'transfer_date'      => ['required', 'date'],
            'remarks'            => ['nullable', 'string'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.item_id'    => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.color_id'   => ['nullable', 'integer', 'exists:inv_colors,id'],
            'items.*.size_id'    => ['nullable', 'integer', 'exists:inv_sizes,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.0001'],
        ];
    }
}
