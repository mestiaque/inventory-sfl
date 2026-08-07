<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('operator') ? 'inv_operator.edit' : 'inv_operator.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $operatorId = $this->route('operator')?->id;

        return [
            'name'        => ['required', 'string', 'max:150'],
            'code'        => ['required', 'string', 'max:50', 'unique:inv_operators,code,' . $operatorId],
            'designation' => ['required', 'in:operator,store_incharge,store_manager'],
            'user_id'     => ['required', 'integer', 'exists:users,id', 'unique:inv_operators,user_id,' . $operatorId],
            'employee_id' => ['nullable', 'integer', 'exists:hr_employees,id'],
            'store_id'    => ['nullable', 'required_unless:designation,operator', 'integer', 'exists:inv_stores,id'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }
}
