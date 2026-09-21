<?php

namespace ME\SflInventory\Models;

use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvPurchaseRequisitionItem extends Model
{
    use HasAudit;
    use HasFactory;

    protected $table = 'inv_purchase_requisition_items';

    protected $fillable = ['purchase_requisition_id', 'item_id', 'color_id', 'size_id', 'requested_qty', 'approved_qty', 'converted_qty'];

    protected $casts = [
        'requested_qty' => 'decimal:4',
        'approved_qty'  => 'decimal:4',
        'converted_qty' => 'decimal:4',
    ];

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(InvPurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(InvColor::class, 'color_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(InvSize::class, 'size_id');
    }
}
