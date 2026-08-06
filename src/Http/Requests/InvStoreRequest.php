<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('store') ? 'inv_store.edit' : 'inv_store.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $storeId = $this->route('store')?->id;

        return [
            'name'      => ['required', 'string', 'max:150'],
            'code'      => ['required', 'string', 'max:50', 'unique:inv_stores,code,' . $storeId],
            'type'      => ['required', 'in:raw_material,accessories,finished_goods'],
            'address'   => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
