<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * The stock ledger. Insert-only — the entire "never store current stock"
 * inventory-engine rule depends on this table being an immutable audit trail;
 * see ME\SflInventory\Services\StockService for how balances are derived from it.
 */
class InvStockTransaction extends Model
{
    use HasFactory;

    protected $table = 'inv_stock_transactions';

    protected $fillable = [
        'item_id', 'store_id', 'transaction_date', 'transaction_type', 'qty_in', 'qty_out',
        'rate', 'value', 'reference_type', 'reference_id', 'department_id', 'remarks', 'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'qty_in'  => 'decimal:4',
        'qty_out' => 'decimal:4',
        'rate'    => 'decimal:2',
        'value'   => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'store_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(InvDepartment::class, 'department_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForItemStore(Builder $query, int $itemId, int $storeId): Builder
    {
        return $query->where('item_id', $itemId)->where('store_id', $storeId);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Ledger rows are insert-only. Corrections must be posted as new,
     * offsetting rows via StockService::post(), never edited in place.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \RuntimeException('inv_stock_transactions rows are immutable; post an offsetting entry instead.');
    }

    public function delete(): ?bool
    {
        throw new \RuntimeException('inv_stock_transactions rows are immutable; post an offsetting entry instead.');
    }
}
