<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use ME\SflInventory\Models\InvRequisitionItem;
use ME\SflInventory\Services\StockService;

class InvRequisitionApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->input('decision') === 'reject' ? 'inv_requisition.reject' : 'inv_requisition.approve';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        return [
            'decision'                 => ['required', 'in:approve,reject'],
            'approval_remarks'         => ['nullable', 'string'],
            'items'                    => ['required_if:decision,approve', 'array'],
            'items.*.id'               => ['required_with:items', 'integer', 'exists:inv_requisition_items,id'],
            'items.*.approved_qty'     => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Approving reserves stock, so it can't approve more than the store has
     * available right now (current stock minus every other approved
     * requisition's reservation). Lines repeating an item+variant are summed.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->input('decision') !== 'approve' || $v->errors()->isNotEmpty()) {
                return;
            }

            $requisition = $this->route('requisition');
            $stock = app(StockService::class);

            $wanted = [];
            foreach ((array) $this->input('items', []) as $index => $line) {
                $reqItem = InvRequisitionItem::with('item')->where('requisition_id', $requisition->id)->find($line['id'] ?? null);
                if (! $reqItem) {
                    continue;
                }
                $key = "{$reqItem->item_id}|{$reqItem->color_id}|{$reqItem->size_id}";
                $wanted[$key] ??= ['line' => $reqItem, 'qty' => 0, 'index' => $index];
                $wanted[$key]['qty'] += (float) ($line['approved_qty'] ?? 0);
            }

            $buyerStore = \ME\SflInventory\Models\InvStore::whereKey($requisition->store_id)->value('type') === \ME\SflInventory\Models\InvStore::TYPE_BUYER;

            foreach ($wanted as $w) {
                $line = $w['line'];
                if ($buyerStore) {
                    $balance = $stock->styleBalance($line->item_id, $requisition->store_id, $requisition->buyer_id, $requisition->style, $requisition->msfl_style_id, $line->color_id, $line->size_id);
                    if ($w['qty'] > $balance['balance'] + 0.0001) {
                        $v->errors()->add(
                            "items.{$w['index']}.approved_qty",
                            "\"{$line->item?->item_code} — {$line->item?->item_name}\": style " . ($requisition->style ?: '(no style)') . ' only has ' . inv_qty(max($balance['balance'], 0)) . ' left in the Buyer Store.'
                        );
                        continue;
                    }
                }

                $available = $stock->availableStock($line->item_id, $requisition->store_id, $line->color_id, $line->size_id, $requisition->id);
                if ($w['qty'] > $available + 0.0001) {
                    $v->errors()->add(
                        "items.{$w['index']}.approved_qty",
                        "\"{$line->item?->item_code} — {$line->item?->item_name}\": approving " . inv_qty($w['qty']) . ', but only ' . inv_qty(max($available, 0)) . ' is available in the store right now.'
                    );
                }
            }
        });
    }
}
