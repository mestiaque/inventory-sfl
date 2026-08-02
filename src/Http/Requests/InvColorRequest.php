<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('color') ? 'inv_color.edit' : 'inv_color.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:150'],
            'hex_code'  => ['nullable', 'string', 'max:7'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
