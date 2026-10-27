<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvIssueReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_issue.return');
    }

    public function rules(): array
    {
        return [
            'return_date'          => ['required', 'date'],
            'reason'               => ['required', 'string', 'max:1000'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.issue_item_id' => ['required', 'integer', 'exists:inv_issue_items,id'],
            'items.*.returned_qty' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
