<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use ME\SflInventory\Http\Requests\Concerns\PicksMerchandisingStyle;

class InvProductionConsumptionRequest extends FormRequest
{
    use PicksMerchandisingStyle;

    /** Buyer → Style → PO from Merchandising (partials.mer-buyer-style), all optional. */
    protected function prepareForValidation(): void
    {
        $this->splitLegacyStyle();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->has('msfl_buyer_id')) {
                $this->validatePick($v, false);
            }
        });
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_production.add');
    }

    public function rules(): array
    {
        return [
            'department_id'          => ['required', 'integer', 'exists:inv_departments,id'],
            'store_id'               => ['required', 'integer', 'exists:inv_stores,id'],
            'issue_id'               => ['nullable', 'integer', 'exists:inv_issues,id'],
            'style'                  => ['nullable', 'string', 'max:150'],
            'order_ref'              => ['nullable', 'string', 'max:150'],
            'msfl_style_id'            => ['nullable', 'integer'],
            'msfl_order_po_id' => ['nullable', 'integer'],
            'msfl_buyer_id'            => ['nullable', 'integer'],
            'consumption_date'       => ['required', 'date'],
            'remarks'                => ['nullable', 'string'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.item_id'        => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.consumed_qty'   => ['required', 'numeric', 'min:0'],
            'items.*.waste_qty'      => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
