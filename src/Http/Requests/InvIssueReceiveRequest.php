<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvIssueReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_issue.receive');
    }

    public function rules(): array
    {
        return [
            'department_receive_remarks'         => ['nullable', 'string'],
            'items'                              => ['required', 'array', 'min:1'],
            'items.*.id'                         => ['required', 'integer', 'exists:inv_issue_items,id'],
            'items.*.department_received_qty'    => ['required', 'numeric', 'min:0'],
        ];
    }
}
