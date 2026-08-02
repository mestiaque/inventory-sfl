<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvStockAdjustmentItem extends Model
{
    use HasFactory;

    protected $table = 'inv_stock_adjustment_items';

    protected $fillable = ['adjustment_id', 'item_id', 'system_qty', 'physical_qty', 'difference_qty', 'remarks'];

    protected $casts = [
        'system_qty'     => 'decimal:4',
        'physical_qty'   => 'decimal:4',
        'difference_qty' => 'decimal:4',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InvStockAdjustment::class, 'adjustment_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
