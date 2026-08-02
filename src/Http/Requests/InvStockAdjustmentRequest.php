<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_adjustment.add');
    }

    public function rules(): array
    {
        return [
            'store_id'                => ['required', 'integer', 'exists:inv_stores,id'],
            'adjustment_date'         => ['required', 'date'],
            'type'                    => ['required', 'in:damage,lost,excess,physical_count'],
            'remarks'                 => ['nullable', 'string'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.item_id'         => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.physical_qty'    => ['required', 'numeric', 'min:0'],
        ];
    }
}
