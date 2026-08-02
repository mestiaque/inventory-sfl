<?php

namespace ME\SflInventory\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use ME\SflInventory\Models\InvOperator;

/**
 * Row-level visibility for transactional list screens (module 2's
 * permission-based access, refined per-user):
 *
 * - A logged-in user with no InvOperator profile (regular admin/staff) is
 *   never restricted — this is opt-in, not a global permission overhaul.
 * - A user tagged 'operator' sees only records they created themselves.
 * - A user tagged 'store_incharge'/'store_manager' sees every record for
 *   their assigned store, regardless of who created it.
 *
 * Applied at the top of each transactional controller's index() query.
 */
class InvOperatorScopeService
{
    private ?InvOperator $operator = null;
    private bool|int|null $resolvedForUserId = false;

    /**
     * Cached per authenticated user id (not just "resolved once") — this
     * service is registered as a container singleton, so within a single
     * request that's a no-op guard, but it also keeps re-authentication
     * (e.g. in tests, or auth()->login() switching users mid-process) correct.
     */
    public function current(): ?InvOperator
    {
        $userId = Auth::id();

        if ($this->resolvedForUserId !== $userId) {
            $this->resolvedForUserId = $userId;
            $this->operator = $userId ? InvOperator::active()->where('user_id', $userId)->first() : null;
        }

        return $this->operator;
    }

    /**
     * Restrict a query to a single store_id column (most transactional
     * tables: inv_issues.store_id, inv_grns.store_id, inv_requisitions.store_id, ...).
     */
    public function applyToStore(Builder $query, string $storeColumn = 'store_id', string $actorColumn = 'created_by'): Builder
    {
        $operator = $this->current();
        if (! $operator) {
            return $query;
        }

        if ($operator->isStoreScoped()) {
            return $operator->store_id ? $query->where($storeColumn, $operator->store_id) : $query->whereRaw('1 = 0');
        }

        return $query->where($actorColumn, $operator->user_id);
    }

    /**
     * Restrict a query where the store could be one of several columns
     * (inv_stock_transfers.from_store_id / to_store_id).
     */
    public function applyToAnyStore(Builder $query, array $storeColumns, string $actorColumn = 'created_by'): Builder
    {
        $operator = $this->current();
        if (! $operator) {
            return $query;
        }

        if ($operator->isStoreScoped()) {
            if (! $operator->store_id) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where(function (Builder $q) use ($storeColumns, $operator) {
                foreach ($storeColumns as $column) {
                    $q->orWhere($column, $operator->store_id);
                }
            });
        }

        return $query->where($actorColumn, $operator->user_id);
    }

    /**
     * Restrict a query purely by the creating/actor user — for documents with
     * no store column at all (e.g. Purchase Orders, which are supplier-facing).
     */
    public function applyToActor(Builder $query, string $actorColumn = 'created_by'): Builder
    {
        $operator = $this->current();
        if (! $operator) {
            return $query;
        }

        return $query->where($actorColumn, $operator->user_id);
    }
}
