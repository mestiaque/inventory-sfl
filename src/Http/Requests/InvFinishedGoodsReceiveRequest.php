<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\MerchandisingLink;

class InvFinishedGoodsReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_fg_receive.add');
    }

    public function rules(): array
    {
        return [
            'receive_date'      => ['required', 'date'],
            'style'             => ['nullable', 'string', 'max:150'],
            'buyer_id'          => ['nullable', 'integer', 'exists:inv_buyers,id'],
            'order_ref'         => ['nullable', 'string', 'max:150'],
            'msfl_style_id'            => ['nullable', 'integer'],
            'msfl_order_po_id' => ['nullable', 'integer'],
            'msfl_buyer_id'            => ['nullable', 'integer'],
            'store_id'          => ['required', 'integer', 'exists:inv_stores,id'],
            'remarks'           => ['nullable', 'string'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.item_id'   => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.quantity'  => ['required', 'numeric', 'min:0.0001'],
        ];
    }

    /** Finished goods only ever go into a Finish Store, against a Merchandising buyer/style. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $store = $this->filled('store_id') ? InvStore::find($this->input('store_id')) : null;
            if ($store && $store->type !== InvStore::TYPE_FINISH) {
                $v->errors()->add('store_id', "{$store->name} is not a " . InvStore::TYPE_LABELS[InvStore::TYPE_FINISH] . '.');
            }

            // With Merchandising: Buyer + Style required (PO optional), and never
            // more than production has packed (per PO, and for the style).
            if (app(MerchandisingLink::class)->available() && ! $v->errors()->has('items')) {
                $qty = (float) collect($this->input('items', []))->sum(fn ($l) => (float) ($l['quantity'] ?? 0));
                $errors = app(MerchandisingLink::class)->validateFinishReceive(
                    $this->integer('msfl_buyer_id') ?: null,
                    $this->integer('msfl_style_id') ?: null,
                    $this->integer('msfl_order_po_id') ?: null,
                    $qty,
                );
                foreach ($errors as $field => $message) {
                    $v->errors()->add($field, $message);
                }
            }
        });
    }
}
