<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvRequisitionItem extends Model
{
    use HasFactory;

    protected $table = 'inv_requisition_items';

    protected $fillable = ['requisition_id', 'item_id', 'requested_qty', 'approved_qty', 'issued_qty', 'remarks'];

    protected $casts = [
        'requested_qty' => 'decimal:4',
        'approved_qty'  => 'decimal:4',
        'issued_qty'    => 'decimal:4',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(InvRequisition::class, 'requisition_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
