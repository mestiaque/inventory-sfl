<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvFinishedGoodsReceiveItem extends Model
{
    use HasFactory;

    protected $table = 'inv_finished_goods_receive_items';

    protected $fillable = ['fg_receive_id', 'item_id', 'quantity', 'remarks'];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function receive(): BelongsTo
    {
        return $this->belongsTo(InvFinishedGoodsReceive::class, 'fg_receive_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
