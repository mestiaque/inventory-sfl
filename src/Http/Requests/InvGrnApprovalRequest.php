<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvGrnApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->input('decision') === 'reject' ? 'inv_grn.reject' : 'inv_grn.approve';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'decision'         => ['required', 'in:approve,reject'],
            'approval_remarks' => ['nullable', 'string'],
        ];
    }
}
