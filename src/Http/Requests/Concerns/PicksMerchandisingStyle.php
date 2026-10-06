<?php

namespace ME\SflInventory\Http\Requests\Concerns;

use Illuminate\Validation\Validator;
use ME\SflInventory\Services\MerchandisingLink;

/**
 * Forms using the shared Buyer → Style → PO picker (partials.mer-buyer-style)
 * or the buyer picker (partials.mer-buyer-select). Buyers and styles come from
 * Merchandising; Inventory no longer keeps its own.
 */
trait PicksMerchandisingStyle
{
    /**
     * An Inventory-only style ("inv:<style no>") goes into the style text,
     * with no Merchandising style or PO. Call from prepareForValidation().
     */
    protected function splitLegacyStyle(): void
    {
        $legacy = app(MerchandisingLink::class)->legacyStylePicked($this->input('msfl_style_id'));
        if ($legacy !== null) {
            $this->merge(['style' => $legacy, 'msfl_style_id' => null, 'msfl_order_po_id' => null]);
        }
    }

    /**
     * Buyer picker: the picked Merchandising buyer becomes the inventory
     * buyer_id (mapped by name). An older record may keep the inventory buyer
     * it already has ($keepInvBuyerId). Call from prepareForValidation().
     */
    protected function mapPickedBuyer(?int $keepInvBuyerId = null, string $field = 'msfl_buyer_id'): void
    {
        if (! $this->has($field)) {
            return;
        }
        $picked = $this->input($field);
        $invBuyerId = app(MerchandisingLink::class)->invBuyerFromPick($picked, $keepInvBuyerId);
        // Something picked that isn't a usable buyer: -1 fails buyer_id's exists rule.
        $this->merge(['buyer_id' => $invBuyerId ?? (filled($picked) ? -1 : null)]);
    }

    /**
     * Checks the pick: buyer approved in Merchandising, style of that buyer,
     * PO of that style. $required: buyer + style must be given; otherwise all
     * optional, but a style needs its buyer.
     */
    protected function validatePick(Validator $v, bool $required): void
    {
        $link = app(MerchandisingLink::class);
        $buyerId = $this->integer('msfl_buyer_id') ?: null;
        $styleId = $this->integer('msfl_style_id') ?: null;
        $poId = $this->integer('msfl_order_po_id') ?: null;
        $legacy = trim((string) $this->input('style'));

        if (! $required && ! $buyerId && ! $styleId && ! $poId && $legacy === '') {
            return;
        }

        if (! $required && $buyerId && ! $styleId && ! $poId && $legacy === '') {
            $errors = $link->buyers()->contains('id', $buyerId) ? [] : ['msfl_buyer_id' => 'Select the buyer from Merchandising (only approved buyers).'];
        } else {
            $errors = $link->validateBuyerReceive($buyerId, $styleId, $poId, $legacy !== '' ? $legacy : null);
        }

        foreach ($errors as $field => $message) {
            $v->errors()->add($field, $message);
        }
    }
}
