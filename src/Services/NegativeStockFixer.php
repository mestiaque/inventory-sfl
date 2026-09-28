<?php

namespace ME\SflInventory\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ME\SflInventory\Models\InvColor;
use ME\SflInventory\Models\InvIssue;
use ME\SflInventory\Models\InvItem;
use ME\SflInventory\Models\InvSize;
use ME\SflInventory\Models\InvStockAdjustment;
use ME\SflInventory\Models\InvStockTransaction;
use ME\SflInventory\Models\InvStore;
use RuntimeException;

/**
 * Corrects item+variant+store balances that went below zero. Two fixes:
 *  - moveIssue(): the challan was posted against the wrong store — give the
 *    stock back to the old store and take it from the right one.
 *  - zeroOut():   the goods really left the store — a Stock Adjustment brings
 *    the balance back to exactly zero.
 * autoFix() applies both automatically. The ledger stays insert-only: every
 * fix posts new offsetting rows, never edits or deletes old ones.
 */
class NegativeStockFixer
{
    private const EPS = 0.0001;

    public function __construct(private readonly StockService $stock)
    {
    }

    /** Every item+variant+store whose ledger balance is below zero, most negative first. */
    public function negativeCombos(?int $itemId = null, ?int $storeId = null): Collection
    {
        return DB::table('inv_stock_transactions')
            ->select('item_id', 'store_id', 'color_id', 'size_id', DB::raw('SUM(qty_in) - SUM(qty_out) as balance'))
            ->when($itemId, fn ($q) => $q->where('item_id', $itemId))
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->groupBy('item_id', 'store_id', 'color_id', 'size_id')
            ->havingRaw('SUM(qty_in) - SUM(qty_out) < ?', [-self::EPS])
            ->orderBy('balance')
            ->get();
    }

    /**
     * Exact item+variant+store match — unlike StockService, a null color/size
     * here means "no variant", not "every variant".
     */
    public function comboQuery(int $itemId, int $storeId, ?int $colorId, ?int $sizeId)
    {
        return InvStockTransaction::query()
            ->where('item_id', $itemId)
            ->where('store_id', $storeId)
            ->tap(fn ($q) => $this->whereVariant($q, $colorId, $sizeId));
    }

    public function comboBalance(int $itemId, int $storeId, ?int $colorId, ?int $sizeId): float
    {
        return (float) $this->comboQuery($itemId, $storeId, $colorId, $sizeId)
            ->selectRaw('COALESCE(SUM(qty_in), 0) - COALESCE(SUM(qty_out), 0) as balance')
            ->value('balance');
    }

    public function whereVariant($query, ?int $colorId, ?int $sizeId): void
    {
        $colorId === null ? $query->whereNull('color_id') : $query->where('color_id', $colorId);
        $sizeId === null ? $query->whereNull('size_id') : $query->where('size_id', $sizeId);
    }

    /**
     * Stores an issue can be re-pointed to: never the Finish Store — raw
     * accessories/buyer goods are only ever issued from General/Buyer stores.
     */
    public function sourceStores(): Collection
    {
        return InvStore::active()
            ->whereIn('type', [InvStore::TYPE_GENERAL, InvStore::TYPE_BUYER])
            ->orderBy('name')
            ->get();
    }

    /**
     * Returns null when $targetStoreId holds enough of every line on the
     * challan, otherwise a human-readable reason why it can't take it.
     */
    public function cannotMoveReason(InvIssue $issue, int $targetStoreId): ?string
    {
        if ($targetStoreId === (int) $issue->store_id) {
            return 'the challan is already posted against that store.';
        }

        $issue->loadMissing('items.item');
        $targetStore = InvStore::find($targetStoreId);

        foreach ($issue->items->groupBy(fn ($line) => "{$line->item_id}|{$line->color_id}|{$line->size_id}") as $lines) {
            $first = $lines->first();
            $qty = (float) $lines->sum('issued_qty');
            $available = $this->stock->currentStock($first->item_id, $targetStoreId, $first->color_id, $first->size_id);
            if ($qty > $available + self::EPS) {
                $itemName = $first->item?->item_name ?? "item #{$first->item_id}";

                return "\"{$itemName}\" needs " . inv_qty($qty) . " but {$targetStore?->name} only has " . inv_qty($available) . '.';
            }
        }

        return null;
    }

    /**
     * $skipStockCheck is only for undoMove() — putting a challan back where
     * it was originally posted restores the ledger to its earlier state, so
     * it must work even if that store is currently short.
     */
    public function moveIssue(InvIssue $issue, int $targetStoreId, ?string $reason = null, bool $skipStockCheck = false): void
    {
        if ($issue->status !== 'approved') {
            throw new RuntimeException("{$issue->issue_no} is not approved, so it never moved stock.");
        }
        if ($targetStoreId === (int) $issue->store_id) {
            throw new RuntimeException("{$issue->issue_no} is already posted against that store.");
        }
        if (! $skipStockCheck && ($why = $this->cannotMoveReason($issue, $targetStoreId))) {
            throw new RuntimeException("Cannot move {$issue->issue_no}: {$why}");
        }

        $issue->loadMissing('items', 'store');
        $targetStore = InvStore::findOrFail($targetStoreId);
        $note = "Store corrected {$issue->issue_no}: {$issue->store?->name} → {$targetStore->name}" . ($reason ? " ({$reason})" : '');

        DB::transaction(function () use ($issue, $targetStoreId, $note) {
            foreach ($issue->items as $line) {
                $this->stock->post([
                    'item_id'          => $line->item_id,
                    'color_id'         => $line->color_id,
                    'size_id'          => $line->size_id,
                    'store_id'         => $issue->store_id,
                    'transaction_date' => $issue->issue_date,
                    'transaction_type' => 'issue_reversal',
                    'qty_in'           => $line->issued_qty,
                    'rate'             => (float) $line->unit_rate,
                    'department_id'    => $issue->department_id,
                    'reference_type'   => 'inv_issue',
                    'reference_id'     => $issue->id,
                    'remarks'          => $note,
                    'created_by'       => auth()->id(),
                ]);

                $outTxn = $this->stock->post([
                    'item_id'          => $line->item_id,
                    'color_id'         => $line->color_id,
                    'size_id'          => $line->size_id,
                    'store_id'         => $targetStoreId,
                    'transaction_date' => $issue->issue_date,
                    'transaction_type' => 'issue',
                    'qty_out'          => $line->issued_qty,
                    'department_id'    => $issue->department_id,
                    'reference_type'   => 'inv_issue',
                    'reference_id'     => $issue->id,
                    'remarks'          => $note,
                    'created_by'       => auth()->id(),
                ]);

                $line->update(['unit_rate' => $outTxn->rate, 'amount' => round($line->issued_qty * $outTxn->rate, 2)]);
            }

            $issue->update(['store_id' => $targetStoreId]);
            $issue->unsetRelation('store');
        });
    }

    /**
     * The store a challan was in before its most recent store correction,
     * or null if it was never moved.
     */
    public function previousStoreId(InvIssue $issue): ?int
    {
        $storeId = InvStockTransaction::where('reference_type', 'inv_issue')
            ->where('reference_id', $issue->id)
            ->where('transaction_type', 'issue_reversal')
            ->where('remarks', 'like', 'Store corrected%')
            ->orderByDesc('id')
            ->value('store_id');

        return $storeId !== null && (int) $storeId !== (int) $issue->store_id ? (int) $storeId : null;
    }

    /** Puts a corrected challan back into the store it was moved out of. */
    public function undoMove(InvIssue $issue): void
    {
        $previous = $this->previousStoreId($issue);
        if ($previous === null) {
            throw new RuntimeException("{$issue->issue_no} was never moved, nothing to undo.");
        }

        $this->moveIssue($issue, $previous, 'undo', skipStockCheck: true);
    }

    /**
     * Challans that an earlier correction moved out of $fromStoreId and into
     * $nowInStoreId, and that include this item+variant.
     */
    public function movedAwayChallans(int $itemId, ?int $colorId, ?int $sizeId, int $fromStoreId, ?int $nowInStoreId = null): Collection
    {
        $ids = $this->comboQuery($itemId, $fromStoreId, $colorId, $sizeId)
            ->where('reference_type', 'inv_issue')
            ->where('transaction_type', 'issue_reversal')
            ->where('remarks', 'like', 'Store corrected%')
            ->pluck('reference_id')
            ->unique();

        return InvIssue::whereIn('id', $ids)
            ->where('status', 'approved')
            ->when($nowInStoreId, fn ($q) => $q->where('store_id', $nowInStoreId), fn ($q) => $q->where('store_id', '!=', $fromStoreId))
            ->with('store', 'items.item', 'items.color', 'items.size', 'department')
            ->orderByDesc('id')
            ->get()
            ->filter(fn ($issue) => $this->previousStoreId($issue) === $fromStoreId)
            ->values();
    }

    /**
     * Raises a Stock Adjustment that brings the balance back to exactly zero,
     * posted immediately when $approve, otherwise left pending in the normal
     * approval queue. Returns null when the balance isn't negative.
     */
    public function zeroOut(int $itemId, int $storeId, ?int $colorId, ?int $sizeId, ?string $reason, bool $approve): ?InvStockAdjustment
    {
        $balance = $this->comboBalance($itemId, $storeId, $colorId, $sizeId);
        if ($balance >= -self::EPS) {
            return null;
        }

        return DB::transaction(function () use ($itemId, $storeId, $colorId, $sizeId, $balance, $reason, $approve) {
            $adjustment = InvStockAdjustment::create([
                'store_id'        => $storeId,
                'adjustment_date' => now()->toDateString(),
                'type'            => 'physical_count',
                'status'          => 'pending',
                'remarks'         => 'Negative stock correction' . ($reason ? ": {$reason}" : ''),
                'created_by'      => auth()->id(),
            ]);

            $adjustment->items()->create([
                'item_id'        => $itemId,
                'color_id'       => $colorId,
                'size_id'        => $sizeId,
                'system_qty'     => $balance,
                'physical_qty'   => 0,
                'difference_qty' => -$balance,
            ]);

            if ($approve) {
                // averageRate() is 0 while the balance is negative, so the
                // correcting inflow is valued at the latest purchase rate.
                $rate = $this->stock->latestRate($itemId, $storeId, $colorId, $sizeId) ?: $this->stock->latestRate($itemId);

                $this->stock->post([
                    'item_id'          => $itemId,
                    'color_id'         => $colorId,
                    'size_id'          => $sizeId,
                    'store_id'         => $storeId,
                    'transaction_date' => $adjustment->adjustment_date,
                    'transaction_type' => 'adjustment',
                    'qty_in'           => -$balance,
                    'rate'             => $rate,
                    'reference_type'   => 'inv_stock_adjustment',
                    'reference_id'     => $adjustment->id,
                    'remarks'          => "Adjustment {$adjustment->adjustment_no} (negative stock correction)",
                    'created_by'       => auth()->id(),
                ]);

                $adjustment->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
            }

            return $adjustment;
        });
    }

    /**
     * Fixes every negative balance. For each one, first moves the issues
     * that took out more than the store held at that moment (the likely
     * wrong-store challans) to a store that does hold the stock; whatever is
     * still negative after that is zeroed with a Stock Adjustment.
     *
     * With $dryRun the whole run happens inside a rolled-back transaction,
     * so the returned log is an exact preview of what Apply will do.
     *
     * @return array<int, array{item: string, store: string, variant: string, before: float, after: float, actions: array<int, string>}>
     */
    public function autoFix(bool $approveAdjustments, bool $dryRun): array
    {
        DB::beginTransaction();

        try {
            $log = [];
            foreach ($this->negativeCombos() as $combo) {
                $log[] = $this->autoFixCombo($combo, $approveAdjustments);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $dryRun ? DB::rollBack() : DB::commit();

        return $log;
    }

    private function autoFixCombo(object $combo, bool $approveAdjustments): array
    {
        $itemId = (int) $combo->item_id;
        $storeId = (int) $combo->store_id;
        $colorId = $combo->color_id !== null ? (int) $combo->color_id : null;
        $sizeId = $combo->size_id !== null ? (int) $combo->size_id : null;

        $item = InvItem::find($itemId);
        $store = InvStore::find($storeId);
        $entry = [
            'item'    => trim(($item?->item_code ?? "#{$itemId}") . ' — ' . ($item?->item_name ?? '')),
            'store'   => $store?->name ?? "#{$storeId}",
            'variant' => implode(' / ', array_filter([
                $colorId ? InvColor::withTrashed()->find($colorId)?->name : null,
                $sizeId ? InvSize::withTrashed()->find($sizeId)?->name : null,
            ])) ?: '—',
            'before'  => $balance = $this->comboBalance($itemId, $storeId, $colorId, $sizeId),
            'actions' => [],
        ];

        // Earlier combos' moves may already have fixed this one.
        if ($balance < -self::EPS) {
            foreach ($this->overIssuedChallans($itemId, $storeId, $colorId, $sizeId) as $issue) {
                if ($balance >= -self::EPS) {
                    break;
                }

                $otherStores = $this->sourceStores()->reject(fn ($s) => $s->id === $storeId);

                $target = $otherStores
                    ->filter(fn ($s) => $this->cannotMoveReason($issue, $s->id) === null)
                    ->sortByDesc(fn ($s) => $this->comboBalance($itemId, $s->id, $colorId, $sizeId))
                    ->first();

                if ($target) {
                    $this->moveIssue($issue, $target->id, 'auto-fix');
                    $entry['actions'][] = "Moved {$issue->issue_no} to {$target->name}";
                } else {
                    // The right store may be short only because earlier
                    // corrections moved smaller challans from this store into
                    // it. Put those back first, then retry — all or nothing.
                    foreach ($otherStores as $candidate) {
                        $undo = $this->movedAwayChallans($itemId, $colorId, $sizeId, $storeId, $candidate->id);
                        if ($undo->isEmpty()) {
                            continue;
                        }

                        try {
                            DB::transaction(function () use ($undo, $issue, $candidate) {
                                foreach ($undo as $moved) {
                                    $this->undoMove($moved);
                                }
                                $this->moveIssue($issue, $candidate->id, 'auto-fix');
                            });
                        } catch (RuntimeException) {
                            continue;
                        }

                        $entry['actions'][] = 'Moved back ' . $undo->pluck('issue_no')->implode(', ') . " to {$entry['store']}";
                        $entry['actions'][] = "Moved {$issue->issue_no} to {$candidate->name}";
                        break;
                    }
                }

                $balance = $this->comboBalance($itemId, $storeId, $colorId, $sizeId);
            }

            if ($balance < -self::EPS) {
                $adjustment = $this->zeroOut($itemId, $storeId, $colorId, $sizeId, 'auto-fix', $approveAdjustments);
                $entry['actions'][] = "Stock Adjustment {$adjustment->adjustment_no} +" . inv_qty(-$balance)
                    . ($approveAdjustments ? '' : ' (waiting for approval)');
                if ($approveAdjustments) {
                    $balance = $this->comboBalance($itemId, $storeId, $colorId, $sizeId);
                }
            }
        }

        $entry['after'] = $balance;

        return $entry;
    }

    /**
     * Approved challans from this store that took out more of this
     * item+variant than the store held at the moment they were posted —
     * largest first, since one big wrong-store challan is the usual culprit.
     */
    private function overIssuedChallans(int $itemId, int $storeId, ?int $colorId, ?int $sizeId): Collection
    {
        $running = 0;
        $overIssued = [];
        foreach ($this->comboQuery($itemId, $storeId, $colorId, $sizeId)->orderBy('id')->get() as $txn) {
            $out = (float) $txn->qty_out;
            if ($txn->transaction_type === 'issue' && $txn->reference_type === 'inv_issue' && $out > 0 && $out > $running + self::EPS) {
                $overIssued[$txn->reference_id] = ($overIssued[$txn->reference_id] ?? 0) + $out;
            }
            $running += (float) $txn->qty_in - $out;
        }

        return InvIssue::whereIn('id', array_keys($overIssued))
            ->where('store_id', $storeId)
            ->where('status', 'approved')
            ->get()
            ->sortByDesc(fn ($issue) => $overIssued[$issue->id])
            ->values();
    }
}
