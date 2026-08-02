<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvProductionConsumptionItem extends Model
{
    use HasFactory;

    protected $table = 'inv_production_consumption_items';

    protected $fillable = ['consumption_id', 'item_id', 'consumed_qty', 'waste_qty', 'remarks'];

    protected $casts = [
        'consumed_qty' => 'decimal:4',
        'waste_qty'    => 'decimal:4',
    ];

    public function consumption(): BelongsTo
    {
        return $this->belongsTo(InvProductionConsumption::class, 'consumption_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
