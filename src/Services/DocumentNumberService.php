<?php

namespace ME\SflInventory\Services;

use Illuminate\Support\Facades\DB;

/**
 * Generates unique, sequential document numbers (PO-000001, GRN-000001, ...)
 * for any Inventory document. Mirrors ME\AccSfl\Services\VoucherNumberService.
 */
class DocumentNumberService
{
    public function next(string $modelClass, string $column, string $prefix, int $pad = 6): string
    {
        return DB::transaction(function () use ($modelClass, $column, $prefix, $pad) {
            $last = $modelClass::withTrashed()
                ->where($column, 'like', "{$prefix}-%")
                ->lockForUpdate()
                ->max(DB::raw("CAST(SUBSTRING_INDEX(`{$column}`, '-', -1) AS UNSIGNED)"));

            $next = str_pad((string) ((int) $last + 1), $pad, '0', STR_PAD_LEFT);

            return "{$prefix}-{$next}";
        });
    }
}
