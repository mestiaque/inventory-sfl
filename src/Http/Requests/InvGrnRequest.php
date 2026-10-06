<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use ME\SflInventory\Http\Requests\Concerns\ValidatesItemStore;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\MerchandisingLink;

class InvGrnRequest extends FormRequest
{
    use ValidatesItemStore;

    public function authorize(): bool
    {
        $ability = $this->route('grn') ? 'inv_grn.edit' : 'inv_grn.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'purchase_order_id'             => [
                'required_if:source_type,purchase',
                'nullable',
                'integer',
                Rule::exists('inv_purchase_orders', 'id')->where(fn ($q) => $q->whereIn('status', ['approved', 'received'])),
            ],
            'source_type'                   => ['required', 'in:purchase,buyer_supplied'],
            'store_id'                      => ['required', 'integer', 'exists:inv_stores,id'],
            'supplier_id'                   => ['required_if:source_type,purchase', 'nullable', 'integer', 'exists:inv_suppliers,id'],
            // With Merchandising installed a Buyer Store receive names the buyer from
            // Merchandising (msfl_buyer_id) and the inventory buyer is derived from it.
            'buyer_id'                      => $this->usesMerchandisingBuyer()
                ? ['nullable', 'integer', 'exists:inv_buyers,id']
                : ['required_if:source_type,buyer_supplied', 'nullable', 'integer', 'exists:inv_buyers,id'],
            'style'                         => ['nullable', 'string', 'max:150'],
            'order_ref'                     => ['nullable', 'string', 'max:150'],
            'msfl_style_id'                  => ['nullable', 'integer'],
            'msfl_order_po_id'      => ['nullable', 'integer'],
            'msfl_buyer_id'                  => ['nullable', 'integer'],
            'challan_invoice_no'            => ['nullable', 'string', 'max:100'],
            'receive_date'                  => ['required', 'date'],
            'received_by'                   => ['nullable', 'integer', 'exists:hr_employees,id'],
            'remarks'                       => ['nullable', 'string'],
            'items'                         => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['nullable', 'integer', 'exists:inv_purchase_order_items,id'],
            'items.*.item_id'               => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.color_id'              => ['nullable', 'integer', 'exists:inv_colors,id'],
            'items.*.size_id'               => ['nullable', 'integer', 'exists:inv_sizes,id'],
            'items.*.ordered_qty'           => ['nullable', 'numeric', 'min:0'],
            'items.*.received_qty'          => ['required', 'numeric', 'min:0.0001'],
            'items.*.rejected_qty'          => ['nullable', 'numeric', 'min:0'],
            'items.*.rate'                  => ['required', 'numeric', 'min:0'],
            'items.*.lot_no'                => ['nullable', 'string', 'max:100'],
            'items.*.batch_no'              => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date'           => ['nullable', 'date'],
        ];
    }

    /**
     * The style select also lists Inventory-only styles as "inv:<style no>"
     * (MerchandisingLink::legacyStyles()): those go into the style text, with
     * no Merchandising style or PO.
     */
    protected function prepareForValidation(): void
    {
        $legacy = app(MerchandisingLink::class)->legacyStylePicked($this->input('msfl_style_id'));
        if ($this->usesMerchandisingBuyer() && $legacy !== null) {
            $this->merge([
                'style' => $legacy,
                'msfl_style_id' => null,
                'msfl_order_po_id' => null,
            ]);
        }
    }

    /** The receive kind — taken from the saved GRN on edit, never from the form. */
    public function sourceType(): ?string
    {
        return $this->route('grn')?->source_type ?? $this->input('source_type');
    }

    /**
     * Buyer Store receives must name a Merchandising buyer + style: always for
     * new ones; on edit only if the GRN was already linked (older, unlinked
     * receipts stay editable the old way).
     */
    public function usesMerchandisingBuyer(): bool
    {
        if ($this->sourceType() !== 'buyer_supplied' || ! app(MerchandisingLink::class)->available()) {
            return false;
        }

        $grn = $this->route('grn');

        return ! $grn || $grn->msfl_buyer_id;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // Each receive kind goes into its own store kind only.
            $wanted = InvStore::storeTypeFor((string) $this->sourceType());
            $store = $this->filled('store_id') ? InvStore::find($this->input('store_id')) : null;
            if ($wanted && $store && $store->type !== $wanted) {
                $v->errors()->add('store_id', "{$store->name} is not a " . InvStore::TYPE_LABELS[$wanted] . ' — this receive must go into one.');
            }

            $grn = $this->route('grn');
            $this->validateItemStores($v, $grn?->store_id ?? ($store?->id), $grn?->items->pluck('item_id') ?? []);

            if ($this->usesMerchandisingBuyer()) {
                $errors = app(MerchandisingLink::class)->validateBuyerReceive(
                    $this->integer('msfl_buyer_id') ?: null,
                    $this->integer('msfl_style_id') ?: null,
                    $this->integer('msfl_order_po_id') ?: null,
                    $this->input('style'),
                );
                foreach ($errors as $field => $message) {
                    $v->errors()->add($field, $message);
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'purchase_order_id.required_if' => 'Select a Store Order — a purchase challan cannot be received without one.',
            'purchase_order_id.exists'       => 'This purchase order has not been approved yet, so a challan cannot be received against it.',
        ];
    }
}
