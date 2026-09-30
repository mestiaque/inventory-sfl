<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvRequisitionPurposeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('requisition_purpose') ? 'inv_requisition_purpose.edit' : 'inv_requisition_purpose.add';

        return (bool) $this->user()?->can($ability);
    }

    /** Code defaults to a slug of the name; it's what requisitions store, so it can't change once used. */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('code') && $this->filled('name')) {
            $this->merge(['code' => \Illuminate\Support\Str::slug($this->input('name'), '_')]);
        }
    }

    public function rules(): array
    {
        $purpose = $this->route('requisition_purpose');

        return [
            'name'       => ['required', 'string', 'max:150'],
            'code'       => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('inv_requisition_purposes', 'code')->ignore($purpose?->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $purpose = $this->route('requisition_purpose');
            if ($purpose && $this->input('code') !== $purpose->code && $purpose->isReferenced()) {
                $v->errors()->add('code', 'This code is already used by requisitions and cannot be changed.');
            }
        });
    }
}
