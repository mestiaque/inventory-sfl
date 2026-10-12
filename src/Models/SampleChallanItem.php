<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SampleChallanItem extends Model
{
    protected $table = 'inv_sample_challan_items';

    protected $fillable = [
        'sample_challan_id', 'sequence', 'msfl_order_po_id', 'item_name', 'po_no', 'style_no', 'color_name',
        'size_id', 'buyer_sample_qty', 'pp_sample_qty', 'unit', 'remarks',
    ];

    public function sampleChallan(): BelongsTo
    {
        return $this->belongsTo(SampleChallan::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(InvSize::class, 'size_id');
    }

    public function getTotalQtyAttribute(): int
    {
        return (int) $this->buyer_sample_qty + (int) $this->pp_sample_qty;
    }
}
