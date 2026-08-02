<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvStockTransferReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_transfer.receive');
    }

    public function rules(): array
    {
        return [
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.id'                => ['required', 'integer', 'exists:inv_stock_transfer_items,id'],
            'items.*.received_qty'      => ['required', 'numeric', 'min:0'],
        ];
    }
}
