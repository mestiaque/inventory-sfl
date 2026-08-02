<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('brand') ? 'inv_brand.edit' : 'inv_brand.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
