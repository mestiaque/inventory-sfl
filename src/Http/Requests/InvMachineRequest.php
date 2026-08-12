<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('machine') ? 'inv_machine.edit' : 'inv_machine.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $machineId = $this->route('machine')?->id;

        return [
            'name'          => ['required', 'string', 'max:150'],
            'code'          => ['required', 'string', 'max:50', 'unique:inv_machines,code,' . $machineId],
            'model'         => ['nullable', 'string', 'max:150'],
            'origin'        => ['nullable', 'string', 'max:150'],
            'type'          => ['nullable', 'string', 'max:150'],
            'color'         => ['nullable', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'department_id' => ['nullable', 'integer', 'exists:inv_departments,id'],
            'section'       => ['nullable', 'string', 'max:150'],
            'line'          => ['nullable', 'string', 'max:150'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }
}
