<?php

namespace ME\SflInventory\Services;

use Illuminate\Support\Collection;
use ME\SflInventory\Models\InvBuyer;

/**
 * Bridge to Merchandising v2 (merchandising-sfl, which also runs production)
 * for the Buyer and Finish stores: goods must belong to a Buyer / Style (and
 * optionally an order PO) that Merchandising already knows — picked once,
 * never retyped.
 *
 * Everything here is a no-op when Merchandising isn't installed, so this
 * package keeps working on its own (the legacy inventory-buyer + free-text
 * style form is used then).
 */
class MerchandisingLink
{
    public function available(): bool
    {
        return class_exists(\ME\MerchandisingSfl\Models\Buyer::class)
            && class_exists(\ME\MerchandisingSfl\Models\Style::class)
            && class_exists(\ME\MerchandisingSfl\Models\OrderPo::class);
    }

    /** Active Merchandising buyers. */
    public function buyers(): Collection
    {
        return $this->buyersCache ??= $this->available()
            ? \ME\MerchandisingSfl\Models\Buyer::query()->active()->orderBy('name')->get(['id', 'code', 'name'])
            : collect();
    }

    /** Active styles, each carrying buyer_id so the form can filter by buyer. */
    public function styles(): Collection
    {
        return $this->available()
            ? \ME\MerchandisingSfl\Models\Style::query()->active()->orderBy('style_no')->get(['id', 'style_no', 'name', 'buyer_id'])
            : collect();
    }

    /** Value prefix of an Inventory-only style in a style select ("inv:262717"). */
    public const LEGACY_STYLE = 'inv:';

    /** Value prefix of an Inventory-only buyer kept on an older record ("inv-buyer:7"). */
    public const LEGACY_BUYER = 'inv-buyer:';

    private ?Collection $legacyStylesCache = null;

    private ?Collection $buyersCache = null;

    /**
     * Everything the shared Buyer → Style → PO picker (partials.mer-buyer-style)
     * and the buyer picker (partials.mer-buyer-select) need — merge into a form's view data.
     */
    public function formOptions(): array
    {
        $on = $this->available();

        return [
            'merLinked' => $on,
            'merBuyersOptions' => $on ? $this->buyers() : collect(),
            'merStylesOptions' => $on ? $this->styles() : collect(),
            'merLegacyStyles' => $on ? $this->legacyStyles() : collect(),
            'merOrderPosOptions' => $on ? $this->pos() : collect(),
        ];
    }

    /** Select value of a saved record's style: the Merchandising style id, else "inv:<style text>". */
    public function pickedStyleValue(mixed $merStyleId, ?string $style): string
    {
        if ($merStyleId) {
            return (string) $merStyleId;
        }

        return trim((string) $style) !== '' ? self::LEGACY_STYLE . trim($style) : '';
    }

    /** The Merchandising buyer matching an inventory buyer by name (null when none). */
    public function merBuyerIdFor(?int $invBuyerId): ?int
    {
        if (! $invBuyerId || ! $this->available()) {
            return null;
        }
        $name = InvBuyer::withTrashed()->whereKey($invBuyerId)->value('name');

        return $name === null ? null : $this->buyers()->first(fn ($b) => mb_strtolower(trim($b->name)) === mb_strtolower(trim($name)))?->id;
    }

    /**
     * Inventory buyer id for a picked buyer value: a Merchandising buyer id
     * (mapped / created by name), or "inv-buyer:<id>" — an inventory buyer an
     * older record already has (only accepted when it is $keepInvBuyerId).
     * Null when nothing (valid) was picked.
     */
    public function invBuyerFromPick(mixed $picked, ?int $keepInvBuyerId = null): ?int
    {
        $picked = (string) $picked;
        if ($picked === '') {
            return null;
        }
        if (str_starts_with($picked, self::LEGACY_BUYER)) {
            $id = (int) substr($picked, strlen(self::LEGACY_BUYER));

            return $keepInvBuyerId && $id === $keepInvBuyerId ? $id : null;
        }

        return ctype_digit($picked) && $this->buyers()->contains('id', (int) $picked) ? $this->inventoryBuyerId((int) $picked) : null;
    }

    /**
     * Fills the inventory side of a Buyer → Style → PO pick: inventory buyer,
     * style text and order ref, from msfl_buyer_id / msfl_style_id /
     * msfl_order_po_id (an Inventory-only style already sits in `style`).
     */
    public function resolvePick(array $data): array
    {
        $data['buyer_id'] = ! empty($data['msfl_buyer_id']) ? $this->inventoryBuyerId((int) $data['msfl_buyer_id']) : null;
        if (! empty($data['msfl_style_id'])) {
            $data['style'] = $this->styleNo((int) $data['msfl_style_id']);
        } elseif (empty($data['msfl_buyer_id'])) {
            $data['style'] = null;
        }
        $data['order_ref'] = ! empty($data['msfl_order_po_id']) ? $this->orderRef((int) $data['msfl_order_po_id']) : null;

        return $data;
    }

    /**
     * Styles Inventory already knows only as text — from older Buyer Store
     * receives (GRN) and Finish Store receives — for Merchandising buyers matched by name, that are not
     * (yet) a Merchandising style of that buyer. Shown next to the v2 styles
     * until every style lives in Merchandising. Each: style_no, buyer_id (v2),
     * received (in a posted Buyer Store GRN).
     */
    public function legacyStyles(): Collection
    {
        if (! $this->available()) {
            return collect();
        }
        if ($this->legacyStylesCache !== null) {
            return $this->legacyStylesCache;
        }

        $merBuyers = $this->buyers()->keyBy(fn ($b) => mb_strtolower(trim($b->name)));
        $invToMer = InvBuyer::query()->get(['id', 'name'])
            ->mapWithKeys(fn ($b) => [$b->id => $merBuyers->get(mb_strtolower(trim($b->name)))?->id])
            ->filter();
        if ($invToMer->isEmpty()) {
            return collect();
        }

        $known = $this->styles()->mapWithKeys(fn ($s) => [$s->buyer_id . '|' . mb_strtolower(trim($s->style_no)) => true]);

        $grnStyles = \ME\SflInventory\Models\InvGrn::query()
            ->where('source_type', 'buyer_supplied')->where('status', 'posted')->whereIn('buyer_id', $invToMer->keys())
            ->whereNotNull('style')->where('style', '<>', '')
            ->distinct()->get(['buyer_id', 'style'])
            ->each(fn ($g) => $g->received = true);
        $fgStyles = \ME\SflInventory\Models\InvFinishedGoodsReceive::query()
            ->whereIn('buyer_id', $invToMer->keys())->whereNull('msfl_style_id')
            ->whereNotNull('style')->where('style', '<>', '')
            ->distinct()->get(['buyer_id', 'style']);

        // GRN rows first, so unique() keeps the "received" flag.
        return $this->legacyStylesCache = $grnStyles->concat($fgStyles)
            ->map(fn ($g) => (object) ['style_no' => trim($g->style), 'buyer_id' => $invToMer[$g->buyer_id], 'received' => (bool) ($g->received ?? false)])
            ->reject(fn ($s) => isset($known[$s->buyer_id . '|' . mb_strtolower($s->style_no)]))
            ->unique(fn ($s) => $s->buyer_id . '|' . mb_strtolower($s->style_no))
            ->sortBy('style_no', SORT_NATURAL)
            ->values();
    }

    /** The style text of a picked "inv:<style no>" option, or null for a Merchandising style id. */
    public function legacyStylePicked(mixed $picked): ?string
    {
        $picked = (string) $picked;

        return str_starts_with($picked, self::LEGACY_STYLE) ? trim(substr($picked, strlen(self::LEGACY_STYLE))) : null;
    }

    /** Order PO lines of open orders, each carrying style_id and its order number. */
    public function pos(): Collection
    {
        if (! $this->available()) {
            return collect();
        }

        return \ME\MerchandisingSfl\Models\OrderPo::query()
            ->with(['order:id,order_no,status'])
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['draft', 'confirmed']))
            ->latest('id')
            ->get(['id', 'po_no', 'style_id', 'order_id']);
    }

    /**
     * Merchandising styles that have goods in the Buyer Store — at least one
     * posted buyer-supplied receive (GRN) linked to them. Floor requisitions
     * from the Buyer Store can only be raised for these.
     */
    public function receivedStyleIds(): array
    {
        return \ME\SflInventory\Models\InvGrn::query()
            ->where('source_type', 'buyer_supplied')->where('status', 'posted')->whereNotNull('msfl_style_id')
            ->distinct()->pluck('msfl_style_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Returns validation errors for a Buyer Store floor requisition: same
     * buyer/style/PO rules as the receive, plus the style must already have
     * been received into the Buyer Store.
     */
    public function validateBuyerRequisition(?int $buyerId, ?int $styleId, ?int $poId, ?string $legacyStyle = null): array
    {
        $errors = $this->validateBuyerReceive($buyerId, $styleId, $poId, $legacyStyle);

        if (! $errors && ! $styleId && $legacyStyle !== null && $legacyStyle !== '') {
            $received = $this->legacyStyles()->contains(fn ($s) => (int) $s->buyer_id === (int) $buyerId && $s->received
                && mb_strtolower($s->style_no) === mb_strtolower(trim($legacyStyle)));
            if (! $received) {
                $errors['msfl_style_id'] = "Nothing has been received into the Buyer Store for style {$legacyStyle} yet — receive it (GRN) first.";
            }
        } elseif (! $errors && ! in_array((int) $styleId, $this->receivedStyleIds(), true)) {
            $styleNo = $this->styleNo((int) $styleId);
            $errors['msfl_style_id'] = "Nothing has been received into the Buyer Store for style {$styleNo} yet — receive it (GRN) first.";
        }

        return $errors;
    }

    /**
     * Finish Store vs production for a style (all its orders) or one PO:
     *   packed    = pieces Merchandising v2 Production has packed — null when
     *               none of these orders has started production (cutting)
     *               yet, so nothing is enforced for them;
     *   received  = pieces already received into the Finish Store for them;
     *   remaining = packed − received.
     * Production lives in the same system, so the figure is always current.
     */
    public function finishSummary(int $styleId, ?int $poId, bool $refresh = false): array
    {
        $pos = \ME\MerchandisingSfl\Models\OrderPo::query()
            ->when($poId, fn ($q) => $q->whereKey($poId), fn ($q) => $q->where('style_id', $styleId))
            ->get();

        $flow = app(\ME\MerchandisingSfl\Services\ProductionFlow::class);
        $started = \ME\MerchandisingSfl\Models\Production\Cutting::query()->whereIn('order_po_id', $pos->pluck('id'))->exists();
        $packed = $started ? (int) $pos->sum(fn ($po) => $flow->summary($po)['packing']['pass']) : null;

        $received = (float) \ME\SflInventory\Models\InvFinishedGoodsReceiveItem::query()
            ->whereHas('receive', fn ($q) => $poId
                ? $q->where('msfl_order_po_id', $poId)
                : $q->where('msfl_style_id', $styleId))
            ->sum('quantity');

        return [
            'scope' => $poId ? 'po' : 'style',
            'order_qty' => (int) $pos->sum('po_qty'),
            'packed' => $packed,
            'received' => $received,
            'remaining' => $packed === null ? null : max(0, $packed - $received),
            'synced_at' => null,
        ];
    }

    /**
     * Finish Store receive: buyer/style/PO consistency, then never more than
     * production has packed — per PO when one is given, and for the style as
     * a whole in every case.
     */
    public function validateFinishReceive(?int $buyerId, ?int $styleId, ?int $poId, float $qty, ?string $legacyStyle = null): array
    {
        $errors = $this->validateBuyerReceive($buyerId, $styleId, $poId, $legacyStyle);
        // An Inventory-only style has no production in Merchandising: nothing to cap.
        if ($errors || ! $styleId) {
            return $errors;
        }

        $checks = $poId ? [$poId, null] : [null];
        foreach ($checks as $checkPo) {
            $sum = $this->finishSummary((int) $styleId, $checkPo, true);
            if ($sum['packed'] !== null && $qty > $sum['remaining'] + 0.0001) {
                $what = $checkPo ? 'this PO' : 'style ' . $this->styleNo((int) $styleId);
                $errors['items'] = 'Production has packed ' . number_format($sum['packed']) . " pcs for {$what}; "
                    . number_format($sum['received']) . ' already received into the Finish Store, so only '
                    . number_format($sum['remaining']) . ' pcs can be received now (you entered ' . number_format($qty) . ').';
                break;
            }
        }

        return $errors;
    }

    /**
     * Returns validation errors (field => message) for a Buyer Store receive:
     * approved buyer, a style of that buyer, and (if given) a PO of that style.
     */
    public function validateBuyerReceive(?int $buyerId, ?int $styleId, ?int $poId, ?string $legacyStyle = null): array
    {
        $errors = [];

        $buyer = $buyerId ? \ME\MerchandisingSfl\Models\Buyer::query()->active()->find($buyerId) : null;
        if (! $buyer) {
            $errors['msfl_buyer_id'] = 'Select the buyer from Merchandising (only active buyers can receive).';

            return $errors;
        }

        // An Inventory-only style (see legacyStyles()): must be one this buyer already has; no PO.
        if (! $styleId && $legacyStyle !== null && $legacyStyle !== '') {
            $ok = $this->legacyStyles()->contains(fn ($s) => (int) $s->buyer_id === (int) $buyer->id
                && mb_strtolower($s->style_no) === mb_strtolower(trim($legacyStyle)));
            if (! $ok) {
                $errors['msfl_style_id'] = "Select a style of {$buyer->name}.";
            }

            return $errors;
        }

        $style = $styleId ? \ME\MerchandisingSfl\Models\Style::query()->find($styleId) : null;
        if (! $style || (int) $style->buyer_id !== (int) $buyer->id) {
            $errors['msfl_style_id'] = "Select a style of {$buyer->name} from Merchandising.";

            return $errors;
        }

        if ($poId) {
            $po = \ME\MerchandisingSfl\Models\OrderPo::query()->find($poId);
            if (! $po || (int) $po->style_id !== (int) $style->id) {
                $errors['msfl_order_po_id'] = "That PO is not an order of style {$style->style_no}.";
            }
        }

        return $errors;
    }

    /** The inventory buyer matching a Merchandising buyer by name, without creating one. */
    public function findInventoryBuyerId(int $merBuyerId): ?int
    {
        $name = \ME\MerchandisingSfl\Models\Buyer::query()->whereKey($merBuyerId)->value('name');

        return $name === null ? null : InvBuyer::query()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])->value('id');
    }

    /**
     * Inventory keeps its own buyer list (reports group by it). Map the
     * Merchandising buyer onto it by name, creating the inventory buyer the
     * first time — so nobody has to enter the buyer twice.
     */
    public function inventoryBuyerId(int $merBuyerId): int
    {
        $merBuyer = \ME\MerchandisingSfl\Models\Buyer::query()->findOrFail($merBuyerId);

        $invBuyer = InvBuyer::query()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($merBuyer->name))])->first();

        if ($invBuyer) {
            return $invBuyer->id;
        }

        // inv_buyers.code is unique: keep the Merchandising code when free.
        $base = $merBuyer->code ?: 'MER-' . $merBuyer->id;
        $code = $base;
        for ($n = 2; InvBuyer::withTrashed()->where('code', $code)->exists(); $n++) {
            $code = "{$base}-{$n}";
        }

        return InvBuyer::create([
            'name' => $merBuyer->name,
            'code' => $code,
            'is_active' => true,
            'created_by' => auth()->id(),
        ])->id;
    }

    public function styleNo(int $styleId): ?string
    {
        return \ME\MerchandisingSfl\Models\Style::query()->whereKey($styleId)->value('style_no');
    }

    /** "PO-123 (ORD-2026-0001)" — the order reference printed on the GRN. */
    public function orderRef(int $poId): ?string
    {
        $po = \ME\MerchandisingSfl\Models\OrderPo::query()->with('order:id,order_no')->find($poId);

        return $po ? trim($po->po_no . ($po->order ? " ({$po->order->order_no})" : '')) : null;
    }
}
