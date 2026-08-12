<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvBrokenNeedleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('broken_needle') ? 'inv_broken_needle.edit' : 'inv_broken_needle.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'employee_id'   => ['required', 'integer', 'exists:hr_employees,id'],
            'department_id' => ['nullable', 'integer', 'exists:inv_departments,id'],
            'machine_id'    => ['required', 'integer', 'exists:inv_machines,id'],
            'needle_type'   => ['required', 'string', 'max:100'],
            'needle_size'   => ['required', 'string', 'max:50'],
            'buyer_id'      => ['nullable', 'integer', 'exists:inv_buyers,id'],
            'style'         => ['nullable', 'string', 'max:150'],
            'broken_date'   => ['required', 'date'],
            'quantity'      => ['required', 'integer', 'min:1'],
            'remarks'       => ['nullable', 'string'],
        ];
    }
}
