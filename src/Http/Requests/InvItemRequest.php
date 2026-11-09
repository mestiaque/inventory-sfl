<?php

namespace ME\SflInventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use ME\SflInventory\Http\Requests\Concerns\PicksMerchandisingStyle;

class InvItemRequest extends FormRequest
{
    use PicksMerchandisingStyle;

    /**
     * Buyers come from Merchandising (several may be picked). Each pick is
     * mapped to an inventory buyer id into buyer_ids; buyer_id keeps the
     * first one so older screens still show a buyer.
     */
    protected function prepareForValidation(): void
    {
        $item = $this->route('item');
        $field = $this->has('msfl_buyer_ids') ? 'msfl_buyer_ids' : ($this->has('buyer_ids') ? 'buyer_ids' : null);
        if ($field !== null || $this->has('buyer_ids_present')) {
            $link = app(\ME\SflInventory\Services\MerchandisingLink::class);
            $existing = $item ? $item->buyers()->pluck('inv_buyers.id')->push($item->buyer_id)->filter()->map(fn ($id) => (int) $id)->all() : [];
            $ids = [];
            foreach ((array) ($field ? $this->input($field, []) : []) as $picked) {
                if (blank($picked)) {
                    continue;
                }
                if ($field === 'buyer_ids') {
                    $ids[] = (int) $picked;
                    continue;
                }
                $legacyId = str_starts_with((string) $picked, \ME\SflInventory\Services\MerchandisingLink::LEGACY_BUYER)
                    ? (int) substr((string) $picked, strlen(\ME\SflInventory\Services\MerchandisingLink::LEGACY_BUYER)) : null;
                $keep = $legacyId && in_array($legacyId, $existing, true) ? $legacyId : null;
                // Something picked that isn't a usable buyer: -1 fails the exists rule.
                $ids[] = $link->invBuyerFromPick($picked, $keep) ?? -1;
            }
            $ids = array_values(array_unique($ids));
            $this->merge(['buyer_ids' => $ids, 'buyer_id' => $ids[0] ?? null]);
        }

        // These columns are NOT NULL with a DB default — a blank input arrives
        // as null (ConvertEmptyStringsToNull) and an explicit NULL bypasses
        // the default, so fall back to it here.
        foreach (['minimum_stock', 'maximum_stock'] as $field) {
            if ($this->has($field) && $this->input($field) === null) {
                $this->merge([$field => 0]);
            }
        }
        // Opening fields are dropped rather than zeroed so a blank opening
        // stock doesn't trip opening_store_id's required_with rule.
        foreach (['opening_stock', 'opening_value'] as $field) {
            if ($this->has($field) && $this->input($field) === null) {
                $this->getInputSource()->remove($field);
            }
        }
        if ($this->has('item_type') && ! $this->filled('item_type')) {
            $this->merge(['item_type' => 'raw_material']);
        }
    }

    public function authorize(): bool
    {
        $ability = $this->route('item') ? 'inv_item.edit' : 'inv_item.add';

        return (bool) $this->user()?->can($ability);
    }

    /**
     * Replacing an item's existing store needs inv_item.change_store — a
     * plain editor can only set it while the item has none yet.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $item = $this->route('item');
            if (! $item || ! $item->opening_store_id || ! $this->has('opening_store_id')) {
                return;
            }
            if ((int) $this->input('opening_store_id') !== (int) $item->opening_store_id && ! $this->user()->can('inv_item.change_store')) {
                $v->errors()->add('opening_store_id', 'You do not have permission to change this item\'s store.');
            }
            if (! $this->filled('opening_store_id')) {
                $v->errors()->add('opening_store_id', 'An item that already has a store cannot be left without one.');
            }
        });
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
            'buyer_ids'        => ['nullable', 'array'],
            'buyer_ids.*'      => ['integer', 'distinct', 'exists:inv_buyers,id'],
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
