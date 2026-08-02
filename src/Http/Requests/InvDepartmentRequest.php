<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('department') ? 'inv_department.edit' : 'inv_department.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;

        return [
            'name'             => ['required', 'string', 'max:150'],
            'code'             => ['required', 'string', 'max:50', 'unique:inv_departments,code,' . $departmentId],
            'default_store_id' => ['nullable', 'integer', 'exists:inv_stores,id'],
            'is_active'        => ['nullable', 'boolean'],
        ];
    }
}
