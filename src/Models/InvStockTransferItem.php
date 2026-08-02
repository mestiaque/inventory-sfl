<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvStockTransferItem extends Model
{
    use HasFactory;

    protected $table = 'inv_stock_transfer_items';

    protected $fillable = ['transfer_id', 'item_id', 'quantity', 'received_qty', 'remarks'];

    protected $casts = [
        'quantity'     => 'decimal:4',
        'received_qty' => 'decimal:4',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InvStockTransfer::class, 'transfer_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
