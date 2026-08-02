<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvSizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('size') ? 'inv_size.edit' : 'inv_size.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
            'is_active'  => ['nullable', 'boolean'],
        ];
    }
}
