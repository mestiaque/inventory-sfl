<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_items';

    protected $fillable = [
        'item_code', 'item_name', 'category_id', 'sub_category_id', 'department_id', 'supplier_id', 'buyer_id',
        'unit_id', 'brand_id', 'color_id', 'size_id', 'specification', 'item_type', 'minimum_stock', 'maximum_stock',
        'opening_stock', 'opening_value', 'opening_store_id', 'barcode', 'barcode_enabled', 'is_active', 'created_by',
    ];

    protected $casts = [
        'minimum_stock'   => 'decimal:4',
        'maximum_stock'   => 'decimal:4',
        'opening_stock'   => 'decimal:4',
        'opening_value'   => 'decimal:2',
        'is_active'       => 'boolean',
        'barcode_enabled' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(InvItemCategory::class, 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(InvItemCategory::class, 'sub_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InvUnit::class, 'unit_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(InvBrand::class, 'brand_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(InvColor::class, 'color_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(InvSize::class, 'size_id');
    }

    public function openingStore(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'opening_store_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(InvDepartment::class, 'department_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(InvSupplier::class, 'supplier_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(InvBuyer::class, 'buyer_id');
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(InvStockTransaction::class, 'item_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('item_type', $type);
    }

    public function isReferenced(): bool
    {
        return $this->stockTransactions()->exists();
    }
}
