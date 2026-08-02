<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('unit') ? 'inv_unit.edit' : 'inv_unit.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'short_name' => ['required', 'string', 'max:20'],
            'is_active'  => ['nullable', 'boolean'],
        ];
    }
}
