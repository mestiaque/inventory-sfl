<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvBuyerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('buyer') ? 'inv_buyer.edit' : 'inv_buyer.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $buyerId = $this->route('buyer')?->id;

        return [
            'name'      => ['required', 'string', 'max:150'],
            'code'      => ['required', 'string', 'max:50', 'unique:inv_buyers,code,' . $buyerId],
            'address'   => ['nullable', 'string', 'max:255'],
            'contact'   => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
