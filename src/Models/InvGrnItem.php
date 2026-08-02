<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvGrnItem extends Model
{
    use HasFactory;

    protected $table = 'inv_grn_items';

    protected $fillable = [
        'grn_id', 'purchase_order_item_id', 'item_id', 'ordered_qty', 'received_qty', 'rejected_qty',
        'rate', 'amount', 'lot_no', 'batch_no', 'remarks',
    ];

    protected $casts = [
        'ordered_qty'  => 'decimal:4',
        'received_qty' => 'decimal:4',
        'rejected_qty' => 'decimal:4',
        'rate'         => 'decimal:2',
        'amount'       => 'decimal:2',
    ];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(InvGrn::class, 'grn_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(InvPurchaseOrderItem::class, 'purchase_order_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
