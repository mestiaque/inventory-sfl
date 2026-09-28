<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use ME\SflInventory\Http\Requests\Concerns\ValidatesItemStore;
use Illuminate\Validation\Validator;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvStore;
use ME\SflInventory\Services\MerchandisingLink;
use ME\SflInventory\Services\StockService;

class InvRequisitionRequest extends FormRequest
{
    use ValidatesItemStore;

    public function authorize(): bool
    {
        $ability = $this->route('requisition') ? 'inv_requisition.edit' : 'inv_requisition.add';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'department_id'          => ['required', 'integer', 'exists:inv_departments,id'],
            'requisition_for'        => ['nullable', 'in:fabrics,accessories,machine_parts,equipment,stationery'],
            'store_id'               => ['required', 'integer', 'exists:inv_stores,id'],
            'received_by'            => ['nullable', 'integer', 'exists:hr_employees,id'],
            'buyer_id'               => ['nullable', 'integer', 'exists:inv_buyers,id'],
            'style'                  => ['nullable', 'string', 'max:150'],
            'order_ref'              => ['nullable', 'string', 'max:150'],
            'mer_style_id'            => ['nullable', 'integer'],
            'mer_sales_contract_po_id' => ['nullable', 'integer'],
            'mer_buyer_id'            => ['nullable', 'integer'],
            'requisition_date'       => ['required', 'date'],
            'remarks'                => ['nullable', 'string'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.item_id'        => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.color_id'       => ['nullable', 'integer', 'exists:inv_colors,id'],
            'items.*.size_id'        => ['nullable', 'integer', 'exists:inv_sizes,id'],
            'items.*.requested_qty'  => ['required', 'numeric', 'min:0.0001'],
        ];
    }

    /**
     * Buyer / Style come from Merchandising: always on a new requisition; on
     * edit only if it was already linked (older, unlinked ones stay editable
     * the old way).
     */
    public function usesMerchandising(): bool
    {
        if (! app(MerchandisingLink::class)->available()) {
            return false;
        }

        $requisition = $this->route('requisition');

        return ! $requisition || $requisition->mer_buyer_id || $requisition->mer_style_id;
    }

    /** Requisition drawn from the Buyer Store — Buyer + Style then required (PO optional). */
    public function fromBuyerStore(): bool
    {
        return $this->filled('store_id')
            && InvStore::whereKey($this->input('store_id'))->value('type') === InvStore::TYPE_BUYER;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $this->validateItemStores($v, $this->integer('store_id') ?: null, $this->route('requisition')?->items->pluck('item_id') ?? []);

            if ($v->errors()->isEmpty()) {
                $this->validateAgainstStock($v);
            }

            if (! $this->usesMerchandising()) {
                return;
            }

            $link = app(MerchandisingLink::class);
            $buyerId = $this->integer('mer_buyer_id') ?: null;
            $styleId = $this->integer('mer_style_id') ?: null;
            $poId = $this->integer('mer_sales_contract_po_id') ?: null;

            if ($this->fromBuyerStore()) {
                $errors = $link->validateBuyerRequisition($buyerId, $styleId, $poId);
            } elseif ($buyerId || $styleId || $poId) {
                // General Store: buyer/style optional — but if given, they must be consistent.
                $errors = $link->validateBuyerReceive($buyerId, $styleId, $poId);
            } else {
                $errors = [];
            }

            foreach ($errors as $field => $message) {
                $v->errors()->add($field, $message);
            }
        });
    }

    /**
     * A requisition can only ask for what the store can actually give:
     * Current Stock minus what other approved requisitions already reserve.
     * Lines repeating the same item+variant are summed.
     */
    private function validateAgainstStock(Validator $v): void
    {
        $stock = app(StockService::class);
        $storeId = (int) $this->input('store_id');
        $excludeId = $this->route('requisition')?->id;

        $wanted = [];
        foreach ((array) $this->input('items', []) as $index => $line) {
            $item = InvItem::find($line['item_id'] ?? null);
            if (! $item) {
                continue;
            }
            [$colorId, $sizeId] = $item->resolvedVariant(! empty($line['color_id']) ? (int) $line['color_id'] : null, ! empty($line['size_id']) ? (int) $line['size_id'] : null);
            $key = "{$item->id}|{$colorId}|{$sizeId}";
            $wanted[$key] ??= ['item' => $item, 'color_id' => $colorId, 'size_id' => $sizeId, 'qty' => 0, 'index' => $index];
            $wanted[$key]['qty'] += (float) ($line['requested_qty'] ?? 0);
        }

        // Buyer Store: the style (buyer + style) must also have it.
        $style = null;
        if ($this->fromBuyerStore()) {
            $link = app(MerchandisingLink::class);
            $merStyleId = $this->integer('mer_style_id') ?: null;
            $style = $this->usesMerchandising()
                ? [
                    'buyer_id' => $this->integer('mer_buyer_id') ? $link->inventoryBuyerId($this->integer('mer_buyer_id')) : null,
                    'style'    => $merStyleId ? $link->styleNo($merStyleId) : null,
                    'mer'      => $merStyleId,
                ]
                : ['buyer_id' => $this->integer('buyer_id') ?: null, 'style' => $this->input('style'), 'mer' => null];
        }

        foreach ($wanted as $w) {
            if ($style) {
                $balance = $stock->styleBalance($w['item']->id, $storeId, $style['buyer_id'], $style['style'], $style['mer'], $w['color_id'] ? (int) $w['color_id'] : null, $w['size_id'] ? (int) $w['size_id'] : null);
                if ($w['qty'] > $balance['balance'] + 0.0001) {
                    $v->errors()->add(
                        "items.{$w['index']}.requested_qty",
                        "\"{$w['item']->item_code} — {$w['item']->item_name}\": style " . ($style['style'] ?: '(no style)') . ' only has ' . inv_qty(max($balance['balance'], 0)) . ' left in the Buyer Store (received ' . inv_qty($balance['received']) . ', issued ' . inv_qty($balance['issued']) . ').'
                    );
                    continue;
                }
            }

            $available = $stock->availableStock($w['item']->id, $storeId, $w['color_id'] ? (int) $w['color_id'] : null, $w['size_id'] ? (int) $w['size_id'] : null, $excludeId);
            if ($w['qty'] > $available + 0.0001) {
                $v->errors()->add(
                    "items.{$w['index']}.requested_qty",
                    "\"{$w['item']->item_code} — {$w['item']->item_name}\": you asked for " . inv_qty($w['qty']) . ', but only ' . inv_qty(max($available, 0)) . ' is available in this store (current stock minus already-approved requisitions).'
                );
            }
        }
    }
}
