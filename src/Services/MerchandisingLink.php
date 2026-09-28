<?php

namespace ME\SflInventory\Services;

use Illuminate\Support\Collection;
use ME\SflInventory\Models\InvBuyer;

/**
 * Bridge to the Merchandising package for the Buyer Store: goods received
 * from a buyer must belong to a Buyer / Style (and optionally a PO) that
 * Merchandising already knows — picked once, never retyped.
 *
 * Everything here is a no-op when Merchandising isn't installed, so this
 * package keeps working on its own (the legacy inventory-buyer + free-text
 * style form is used then).
 */
class MerchandisingLink
{
    public function available(): bool
    {
        return class_exists(\ME\MerchandisingTrace\Models\Buyer::class)
            && class_exists(\ME\MerchandisingTrace\Models\Style::class)
            && class_exists(\ME\MerchandisingTrace\Models\SalesContractPo::class);
    }

    /** Approved + active Merchandising buyers (unapproved ones can't receive). */
    public function buyers(): Collection
    {
        return $this->available()
            ? \ME\MerchandisingTrace\Models\Buyer::query()->active()->orderBy('name')->get(['id', 'code', 'name'])
            : collect();
    }

    /** Active styles, each carrying buyer_id so the form can filter by buyer. */
    public function styles(): Collection
    {
        return $this->available()
            ? \ME\MerchandisingTrace\Models\Style::query()->active()->orderBy('style_no')->get(['id', 'style_no', 'name', 'buyer_id'])
            : collect();
    }

    /**
     * PO lines, each carrying style_id and its contract number. Store staff see
     * every merchandiser's POs — the merchandiser row-scope is for merchandisers.
     */
    public function pos(): Collection
    {
        if (! $this->available()) {
            return collect();
        }

        return \ME\MerchandisingTrace\Models\SalesContractPo::withoutGlobalScopes()
            ->with(['salesContract' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'contract_no', 'lc_no')])
            ->whereNotIn('status', ['closed'])
            ->latest('id')
            ->get(['id', 'po_no', 'style_id', 'sales_contract_id']);
    }

    /**
     * Merchandising styles that have goods in the Buyer Store — at least one
     * posted buyer-supplied receive (GRN) linked to them. Floor requisitions
     * from the Buyer Store can only be raised for these.
     */
    public function receivedStyleIds(): array
    {
        return \ME\SflInventory\Models\InvGrn::query()
            ->where('source_type', 'buyer_supplied')->where('status', 'posted')->whereNotNull('mer_style_id')
            ->distinct()->pluck('mer_style_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Returns validation errors for a Buyer Store floor requisition: same
     * buyer/style/PO rules as the receive, plus the style must already have
     * been received into the Buyer Store.
     */
    public function validateBuyerRequisition(?int $buyerId, ?int $styleId, ?int $poId): array
    {
        $errors = $this->validateBuyerReceive($buyerId, $styleId, $poId);

        if (! $errors && ! in_array((int) $styleId, $this->receivedStyleIds(), true)) {
            $styleNo = $this->styleNo((int) $styleId);
            $errors['mer_style_id'] = "Nothing has been received into the Buyer Store for style {$styleNo} yet — receive it (GRN) first.";
        }

        return $errors;
    }

    /**
     * Finish Store vs production for a style (all its orders) or one PO:
     *   packed    = pieces production has packed (Merchandising's synced
     *               production progress) — null when production has no record
     *               yet for these orders (then nothing is enforced);
     *   received  = pieces already received into the Finish Store for them;
     *   remaining = packed − received.
     * $refresh re-syncs the orders' production progress first (used when
     * saving, so the check uses today's figure, not the last hourly sync).
     */
    public function finishSummary(int $styleId, ?int $poId, bool $refresh = false): array
    {
        $pos = \ME\MerchandisingTrace\Models\SalesContractPo::withoutGlobalScopes()
            ->when($poId, fn ($q) => $q->whereKey($poId), fn ($q) => $q->where('style_id', $styleId))
            ->get();

        if ($refresh && class_exists(\ME\MerchandisingTrace\Services\ProductionProgressSyncService::class)) {
            $sync = app(\ME\MerchandisingTrace\Services\ProductionProgressSyncService::class);
            foreach ($pos as $po) {
                try {
                    $sync->syncFor($po);
                } catch (\Throwable $e) {
                    report($e); // production not reachable — fall back to the last synced figure
                }
            }
        }

        $progress = \ME\MerchandisingTrace\Models\PoProductionProgress::query()->whereIn('sales_contract_po_id', $pos->pluck('id'))->get();

        $received = (float) \ME\SflInventory\Models\InvFinishedGoodsReceiveItem::query()
            ->whereHas('receive', fn ($q) => $poId
                ? $q->where('mer_sales_contract_po_id', $poId)
                : $q->where('mer_style_id', $styleId))
            ->sum('quantity');

        $packed = $progress->isEmpty() ? null : (int) $progress->sum('packed_qty');

        return [
            'scope' => $poId ? 'po' : 'style',
            'order_qty' => (int) $pos->sum(fn ($po) => $po->effectiveQty()),
            'packed' => $packed,
            'received' => $received,
            'remaining' => $packed === null ? null : max(0, $packed - $received),
            'synced_at' => ($last = $progress->max('synced_at')) ? \Illuminate\Support\Carbon::parse($last)->format('d-M-Y H:i') : null,
        ];
    }

    /**
     * Finish Store receive: buyer/style/PO consistency, then never more than
     * production has packed — per PO when one is given, and for the style as
     * a whole in every case.
     */
    public function validateFinishReceive(?int $buyerId, ?int $styleId, ?int $poId, float $qty): array
    {
        $errors = $this->validateBuyerReceive($buyerId, $styleId, $poId);
        if ($errors) {
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
    public function validateBuyerReceive(?int $buyerId, ?int $styleId, ?int $poId): array
    {
        $errors = [];

        $buyer = $buyerId ? \ME\MerchandisingTrace\Models\Buyer::query()->active()->find($buyerId) : null;
        if (! $buyer) {
            $errors['mer_buyer_id'] = 'Select the buyer from Merchandising (only approved, active buyers can receive).';

            return $errors;
        }

        $style = $styleId ? \ME\MerchandisingTrace\Models\Style::query()->find($styleId) : null;
        if (! $style || (int) $style->buyer_id !== (int) $buyer->id) {
            $errors['mer_style_id'] = "Select a style of {$buyer->name} from Merchandising.";

            return $errors;
        }

        if ($poId) {
            $po = \ME\MerchandisingTrace\Models\SalesContractPo::withoutGlobalScopes()->find($poId);
            if (! $po || (int) $po->style_id !== (int) $style->id) {
                $errors['mer_sales_contract_po_id'] = "That PO is not an order of style {$style->style_no}.";
            }
        }

        return $errors;
    }

    /**
     * Inventory keeps its own buyer list (reports group by it). Map the
     * Merchandising buyer onto it by name, creating the inventory buyer the
     * first time — so nobody has to enter the buyer twice.
     */
    public function inventoryBuyerId(int $merBuyerId): int
    {
        $merBuyer = \ME\MerchandisingTrace\Models\Buyer::query()->findOrFail($merBuyerId);

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
        return \ME\MerchandisingTrace\Models\Style::query()->whereKey($styleId)->value('style_no');
    }

    /** "PO-123 (SC-000045)" — the order reference printed on the GRN. */
    public function orderRef(int $poId): ?string
    {
        $po = \ME\MerchandisingTrace\Models\SalesContractPo::withoutGlobalScopes()
            ->with(['salesContract' => fn ($q) => $q->withoutGlobalScopes()])->find($poId);

        return $po ? trim($po->po_no . ($po->salesContract ? " ({$po->salesContract->contract_no})" : '')) : null;
    }
}
