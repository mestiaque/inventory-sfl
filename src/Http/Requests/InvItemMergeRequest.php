<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InvItemMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_item.merge');
    }

    public function rules(): array
    {
        return [
            'keep_item_id'          => ['required', 'integer', 'exists:inv_items,id'],
            'duplicate_item_ids'    => ['required', 'array', 'min:1'],
            'duplicate_item_ids.*'  => ['integer', 'exists:inv_items,id', 'distinct'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $keepId = (int) $this->input('keep_item_id');
            $duplicateIds = array_map('intval', $this->input('duplicate_item_ids', []));

            if ($keepId && in_array($keepId, $duplicateIds, true)) {
                $validator->errors()->add('duplicate_item_ids', 'The item you are keeping can\'t also be selected as a duplicate to merge away.');
            }
        });
    }
}
