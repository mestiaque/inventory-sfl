<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvGatePassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_gate_pass.add');
    }

    public function rules(): array
    {
        return [
            'gate_pass_date'    => ['required', 'date'],
            'buyer_id'          => ['nullable', 'integer', 'exists:inv_buyers,id'],
            'vehicle_no'        => ['nullable', 'string', 'max:50'],
            'driver_name'       => ['nullable', 'string', 'max:150'],
            'driver_contact'    => ['nullable', 'string', 'max:50'],
            'store_id'          => ['required', 'integer', 'exists:inv_stores,id'],
            'remarks'           => ['nullable', 'string'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.item_id'   => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.quantity'  => ['required', 'numeric', 'min:0.0001'],
        ];
    }
}
