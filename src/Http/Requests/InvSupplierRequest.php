<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->route('supplier') ? 'inv_supplier.edit' : 'inv_supplier.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id;

        return [
            'name'            => ['required', 'string', 'max:150'],
            'code'            => ['required', 'string', 'max:50', 'unique:inv_suppliers,code,' . $supplierId],
            'address'         => ['nullable', 'string', 'max:255'],
            'contact_person'  => ['nullable', 'string', 'max:150'],
            'products_supplied'   => ['nullable', 'string', 'max:255'],
            'relation_since_year' => ['nullable', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
            'phone'           => ['nullable', 'string', 'max:30'],
            'email'           => ['nullable', 'email', 'max:150'],
            'tin_vat'         => ['nullable', 'string', 'max:50'],
            'price_rating'    => ['nullable', 'integer', 'min:1', 'max:5'],
            'quality_rating'  => ['nullable', 'integer', 'min:1', 'max:5'],
            'remarks'         => ['nullable', 'string'],
            'is_active'       => ['nullable', 'boolean'],
        ];
    }
}
