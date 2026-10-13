<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SampleChallanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'items' => collect($this->input('items', []))->filter(fn ($line) => filled($line['msfl_order_po_id'] ?? null))->values()->all(),
        ]);
    }

    public function authorize(): bool
    {
        $ability = $this->route('sample_challan') ? 'inv_sample_challan.edit' : 'inv_sample_challan.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'challan_date' => ['required', 'date'],
            'store_id' => ['nullable', 'integer', 'exists:inv_stores,id'],
            'msfl_buyer_id' => ['nullable', 'integer'],
            'msfl_sample_type_id' => ['nullable', 'integer'],
            'to_name' => ['required', 'string', 'max:255'],
            'to_address' => ['nullable', 'string', 'max:2000'],
            'to_contact' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.msfl_order_po_id' => ['required', 'integer'],
            'items.*.size_id' => ['nullable', 'integer', 'exists:inv_sizes,id'],
            'items.*.buyer_sample_qty' => ['nullable', 'integer', 'min:0'],
            'items.*.pp_sample_qty' => ['nullable', 'integer', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['items.required' => 'Add at least one item line.'];
    }
}
