<?php

namespace ME\SflInventory\Http\Requests\Concerns;

use Illuminate\Validation\Validator;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvStore;

/**
 * Every item lives in exactly one store (inv_items.opening_store_id). A
 * document line may only use an item that has a store, and — when the
 * document itself names a store — only in that store. Items already on a
 * document being edited ($keepItemIds) are let through so old documents
 * stay editable.
 */
trait ValidatesItemStore
{
    protected function validateItemStores(Validator $v, ?int $documentStoreId, iterable $keepItemIds = [], string $itemsKey = 'items'): void
    {
        $keep = collect($keepItemIds)->map(fn ($id) => (int) $id)->all();
        $lines = collect((array) $this->input($itemsKey, []));
        $items = InvItem::with('openingStore')->whereIn('id', $lines->pluck('item_id')->filter())->get()->keyBy('id');
        $storeName = $documentStoreId ? (InvStore::find($documentStoreId)?->name ?? 'this store') : null;

        foreach ($lines as $index => $line) {
            $item = $items->get((int) ($line['item_id'] ?? 0));
            if (! $item || in_array($item->id, $keep, true)) {
                continue;
            }

            $label = "\"{$item->item_code} — {$item->item_name}\"";
            if (! $item->opening_store_id) {
                $v->errors()->add("{$itemsKey}.{$index}.item_id", "{$label} has no store assigned — set its store in Item Master first.");
            } elseif ($documentStoreId && (int) $item->opening_store_id !== $documentStoreId) {
                $v->errors()->add("{$itemsKey}.{$index}.item_id", "{$label} belongs to {$item->openingStore?->name}, not {$storeName}.");
            }
        }
    }
}
