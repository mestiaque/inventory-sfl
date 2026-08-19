<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('item') ? 'inv_item.edit' : 'inv_item.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $itemId = $this->route('item')?->id;
        $isCreate = $itemId === null;

        return [
            'item_code'        => ['required', 'string', 'max:50', 'unique:inv_items,item_code,' . $itemId],
            'item_name'        => ['required', 'string', 'max:200'],
            'category_id'      => ['required', 'integer', 'exists:inv_item_categories,id'],
            'sub_category_id'  => ['nullable', 'integer', 'exists:inv_item_categories,id'],
            'department_id'    => ['nullable', 'integer', 'exists:inv_departments,id'],
            'supplier_id'      => ['nullable', 'integer', 'exists:inv_suppliers,id'],
            'buyer_id'         => ['nullable', 'integer', 'exists:inv_buyers,id'],
            'unit_id'          => ['required', 'integer', 'exists:inv_units,id'],
            'brand_id'         => ['nullable', 'integer', 'exists:inv_brands,id'],
            'color_id'         => ['nullable', 'integer', 'exists:inv_colors,id'],
            'size_id'          => ['nullable', 'integer', 'exists:inv_sizes,id'],
            'specification'    => ['nullable', 'string'],
            'item_type'        => ['nullable', 'in:raw_material,wip,finished_good'],
            'minimum_stock'    => ['nullable', 'numeric', 'min:0'],
            'maximum_stock'    => ['nullable', 'numeric', 'min:0'],
            'opening_stock'    => [$isCreate ? 'nullable' : 'prohibited', 'numeric', 'min:0'],
            'opening_value'    => [$isCreate ? 'nullable' : 'prohibited', 'numeric', 'min:0'],
            'opening_store_id' => ['nullable', 'required_with:opening_stock', 'integer', 'exists:inv_stores,id'],
            'barcode'          => ['nullable', 'string', 'max:100', 'unique:inv_items,barcode,' . $itemId],
            'barcode_enabled'  => ['nullable', 'boolean'],
            'is_active'        => ['nullable', 'boolean'],
        ];
    }
}
