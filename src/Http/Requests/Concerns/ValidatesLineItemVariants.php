<?php

namespace ME\SflInventory\Http\Requests\Concerns;

use Illuminate\Validation\Validator;
use ME\SflInventory\Models\InvItem;

/**
 * An item with no fixed color_id/size_id of its own on the Item Master (a
 * "generic" multi-variant item, e.g. plain "Button") requires an explicit
 * color/size pick on any line that introduces it. An item that already has
 * a fixed color_id/size_id needs no pick — every line just inherits it.
 *
 * A line carried forward from an upstream document (a PO line copying its
 * PR line, a GRN line copying its PO line, an Issue line copying its
 * Requisition line) already submits color_id/size_id as hidden, read-only
 * fields, so this same check passes for those without any extra branching —
 * it only ever blocks a line that is missing a variant pick it actually
 * needs.
 */
trait ValidatesLineItemVariants
{
    protected function validateLineItemVariants(Validator $validator, array $items): void
    {
        $itemIds = collect($items)->pluck('item_id')->filter()->unique();
        if ($itemIds->isEmpty()) {
            return;
        }

        $itemsById = InvItem::whereIn('id', $itemIds)->get(['id', 'color_id', 'size_id'])->keyBy('id');

        foreach ($items as $index => $line) {
            $item = $itemsById->get($line['item_id'] ?? null);
            if (! $item) {
                continue;
            }

            if ($item->color_id === null && empty($line['color_id'])) {
                $validator->errors()->add("items.{$index}.color_id", 'This item has no fixed color — pick a color for this line.');
            }
            if ($item->size_id === null && empty($line['size_id'])) {
                $validator->errors()->add("items.{$index}.size_id", 'This item has no fixed size — pick a size for this line.');
            }
        }
    }
}
