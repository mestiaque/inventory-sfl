<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvItemCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('item_category') ? 'inv_item_category.edit' : 'inv_item_category.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $categoryId = $this->route('item_category')?->id;

        return [
            'name'      => ['required', 'string', 'max:150'],
            'code'      => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'exists:inv_item_categories,id', Rule::notIn([$categoryId])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
