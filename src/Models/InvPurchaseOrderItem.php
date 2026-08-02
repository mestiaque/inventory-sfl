<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvPurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'inv_purchase_order_items';

    protected $fillable = ['purchase_order_id', 'item_id', 'quantity', 'rate', 'amount', 'received_qty', 'remarks'];

    protected $casts = [
        'quantity'     => 'decimal:4',
        'rate'         => 'decimal:2',
        'amount'       => 'decimal:2',
        'received_qty' => 'decimal:4',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(InvPurchaseOrder::class, 'purchase_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
