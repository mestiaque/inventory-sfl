<?php

namespace ME\SflInventory\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time repair for a bug where several transaction types posted qty
 * movements at rate/value = 0 instead of the moving-average cost:
 *  - every 'issue' (unit_rate cast to a decimal string like "0.00", which
 *    `?: null` treats as truthy, so it never fell through to the
 *    average-rate fallback in StockService::post())
 *  - 'transfer' receipts, 'transfer_reversal' (dispatch side) and
 *    'production_consumption_reversal' (all qty_in-only posts with no
 *    'rate' key — StockService::post() only auto-fills the average rate
 *    for qty_out, so a bare qty_in silently defaulted to rate 0)
 *
 * This replays every item's full transaction history in chronological
 * order, per store, recomputing the correct moving-average rate/value for
 * every derived (non-source) row and leaving genuine source-of-truth rows
 * (grn, opening, grn_reversal, finished_goods) untouched. It updates rows
 * directly via the query builder — bypassing InvStockTransaction's
 * application-level immutability guard on purpose, since this is a
 * one-time data repair, not a business operation.
 *
 * Safe to re-run: every value is recomputed from qty columns (which are
 * never touched), never incremented, so re-running produces the same
 * result. Defaults to a dry run; pass --apply to write changes.
 */
class RebuildStockLedgerValues extends Command
{
    protected $signature = 'inventory:rebuild-ledger-values {--apply : Actually write the corrected rate/value columns (default is dry-run)} {--item= : Limit to a single item_id, for spot-checking before a full run}';

    protected $description = 'Recompute moving-average rate/value on stock ledger rows affected by the qty_in/qty_out rate-0 bug';

    private const SOURCE_TYPES = ['grn', 'opening', 'finished_goods'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'Running in APPLY mode — rows will be updated.' : 'Running in DRY-RUN mode — no changes will be written. Pass --apply to write.');

        $itemIds = DB::table('inv_stock_transactions')
            ->when($this->option('item'), fn ($q) => $q->where('item_id', $this->option('item')))
            ->distinct()->pluck('item_id');
        $this->info("Replaying {$itemIds->count()} items...");

        $totalRowsChanged = 0;
        $totalValueDeltaAbs = 0.0;
        $updates = [];

        foreach ($itemIds as $itemId) {
            $rows = DB::table('inv_stock_transactions')
                ->where('item_id', $itemId)
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            $runningQty = [];
            $runningValue = [];
            $dispatchRateByRef = [];
            $consumptionRateByRef = [];

            foreach ($rows as $row) {
                $store = $row->store_id;
                $runningQty[$store] ??= 0.0;
                $runningValue[$store] ??= 0.0;

                $type = $row->transaction_type;
                $qtyIn = (float) $row->qty_in;
                $qtyOut = (float) $row->qty_out;
                $newRate = (float) $row->rate;
                $newValue = (float) $row->value;

                if (in_array($type, self::SOURCE_TYPES, true)) {
                    // Trust the stored rate/value as-is (real purchase/opening cost).
                    $runningQty[$store] += $qtyIn;
                    $runningValue[$store] += $newValue;
                } elseif ($type === 'grn_reversal') {
                    // Trust the stored rate (the original GRN's rate, fixed by design); just recompute value defensively.
                    $newValue = round($qtyOut * $newRate, 2);
                    $runningQty[$store] -= $qtyOut;
                    $runningValue[$store] -= $newValue;
                } elseif ($type === 'transfer' && $qtyIn > 0) {
                    $newRate = $dispatchRateByRef[$row->reference_id] ?? $newRate;
                    $newValue = round($qtyIn * $newRate, 2);
                    $runningQty[$store] += $qtyIn;
                    $runningValue[$store] += $newValue;
                } elseif ($type === 'transfer' && $qtyOut > 0) {
                    $newRate = $runningQty[$store] > 0 ? round($runningValue[$store] / $runningQty[$store], 2) : 0.0;
                    $newValue = round($qtyOut * $newRate, 2);
                    $runningQty[$store] -= $qtyOut;
                    $runningValue[$store] -= $newValue;
                    $dispatchRateByRef[$row->reference_id] = $newRate;
                } elseif ($type === 'transfer_reversal' && $qtyIn > 0) {
                    $newRate = $dispatchRateByRef[$row->reference_id] ?? $newRate;
                    $newValue = round($qtyIn * $newRate, 2);
                    $runningQty[$store] += $qtyIn;
                    $runningValue[$store] += $newValue;
                } elseif ($type === 'transfer_reversal' && $qtyOut > 0) {
                    $newRate = $runningQty[$store] > 0 ? round($runningValue[$store] / $runningQty[$store], 2) : 0.0;
                    $newValue = round($qtyOut * $newRate, 2);
                    $runningQty[$store] -= $qtyOut;
                    $runningValue[$store] -= $newValue;
                } elseif ($type === 'production_consumption') {
                    $newRate = $runningQty[$store] > 0 ? round($runningValue[$store] / $runningQty[$store], 2) : 0.0;
                    $newValue = round($qtyOut * $newRate, 2);
                    $runningQty[$store] -= $qtyOut;
                    $runningValue[$store] -= $newValue;
                    $consumptionRateByRef[$row->reference_id] = $newRate;
                } elseif ($type === 'production_consumption_reversal') {
                    $newRate = $consumptionRateByRef[$row->reference_id] ?? $newRate;
                    $newValue = round($qtyIn * $newRate, 2);
                    $runningQty[$store] += $qtyIn;
                    $runningValue[$store] += $newValue;
                } elseif (in_array($type, ['issue', 'gate_pass'], true)) {
                    $newRate = $runningQty[$store] > 0 ? round($runningValue[$store] / $runningQty[$store], 2) : 0.0;
                    $newValue = round($qtyOut * $newRate, 2);
                    $runningQty[$store] -= $qtyOut;
                    $runningValue[$store] -= $newValue;
                } elseif (in_array($type, ['adjustment', 'adjustment_reversal'], true)) {
                    $rate = $runningQty[$store] > 0 ? round($runningValue[$store] / $runningQty[$store], 2) : 0.0;
                    if ($qtyIn > 0) {
                        $newRate = $rate;
                        $newValue = round($qtyIn * $rate, 2);
                        $runningQty[$store] += $qtyIn;
                        $runningValue[$store] += $newValue;
                    } else {
                        $newRate = $rate;
                        $newValue = round($qtyOut * $rate, 2);
                        $runningQty[$store] -= $qtyOut;
                        $runningValue[$store] -= $newValue;
                    }
                } else {
                    // Unknown/unhandled type — leave untouched, still track its
                    // effect on the running balance so later rows stay correct.
                    $runningQty[$store] += $qtyIn - $qtyOut;
                    $runningValue[$store] += $qtyIn > 0 ? $newValue : -$newValue;
                }

                if (abs($newRate - (float) $row->rate) > 0.005 || abs($newValue - (float) $row->value) > 0.005) {
                    $updates[] = [
                        'id' => $row->id,
                        'old_rate' => $row->rate,
                        'new_rate' => $newRate,
                        'old_value' => $row->value,
                        'new_value' => $newValue,
                    ];
                    $totalRowsChanged++;
                    $totalValueDeltaAbs += abs($newValue - (float) $row->value);
                }
            }
        }

        $this->info("Rows needing correction: {$totalRowsChanged}");
        $this->info('Total |value| delta: ' . number_format($totalValueDeltaAbs, 2));

        if (! $apply) {
            $this->table(['id', 'old_rate', 'new_rate', 'old_value', 'new_value'], array_slice($updates, 0, 20));
            if (count($updates) > 20) {
                $this->line('... and ' . (count($updates) - 20) . ' more rows.');
            }

            return self::SUCCESS;
        }

        DB::transaction(function () use ($updates) {
            foreach ($updates as $u) {
                DB::table('inv_stock_transactions')->where('id', $u['id'])->update([
                    'rate'  => $u['new_rate'],
                    'value' => $u['new_value'],
                ]);
            }
        });

        $this->info("Applied corrections to {$totalRowsChanged} rows.");

        return self::SUCCESS;
    }
}
