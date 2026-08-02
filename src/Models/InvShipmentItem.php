<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvShipmentItem extends Model
{
    use HasFactory;

    protected $table = 'inv_shipment_items';

    protected $fillable = ['shipment_id', 'item_id', 'quantity', 'remarks'];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(InvShipment::class, 'shipment_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
