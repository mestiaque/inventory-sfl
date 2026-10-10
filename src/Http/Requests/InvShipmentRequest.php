<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use ME\SflInventory\Http\Requests\Concerns\PicksMerchandisingStyle;
use ME\SflInventory\Services\MerchandisingLink;

class InvShipmentRequest extends FormRequest
{
    use PicksMerchandisingStyle;

    /** Buyer comes from Merchandising (partials.mer-buyer-select). */
    protected function prepareForValidation(): void
    {
        $this->mapPickedBuyer();
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inv_shipment.add');
    }

    public function rules(): array
    {
        return [
            'shipment_date'     => ['required', 'date'],
            'buyer_id'          => ['nullable', 'integer', 'exists:inv_buyers,id'],
            'invoice_no'        => ['nullable', 'string', 'max:100'],
            'packing_list_no'   => ['nullable', 'string', 'max:100'],
            'store_id'          => ['required', 'integer', 'exists:inv_stores,id'],
            'remarks'           => ['nullable', 'string'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.item_id'   => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.quantity'  => ['required', 'numeric', 'min:0.0001'],
            'items.*.msfl_order_po_id' => ['nullable', 'integer'],
        ];
    }

    /** A line's PO must be a confirmed Merchandising PO of the picked buyer. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $pos = app(MerchandisingLink::class)->shipmentPos();
            $buyerId = $this->integer('msfl_buyer_id') ?: null;
            foreach ((array) $this->input('items', []) as $i => $line) {
                $poId = (int) ($line['msfl_order_po_id'] ?? 0);
                if (! $poId) {
                    continue;
                }
                if (! $pos->has($poId)) {
                    $v->errors()->add("items.{$i}.msfl_order_po_id", 'Row ' . ($i + 1) . ': pick a confirmed PO.');
                } elseif ($buyerId && (int) $pos[$poId]['buyer_id'] !== $buyerId) {
                    $v->errors()->add("items.{$i}.msfl_order_po_id", 'Row ' . ($i + 1) . ': the PO belongs to another buyer.');
                }
            }
        });
    }
}
