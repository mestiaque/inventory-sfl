<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvGatePassItem extends Model
{
    use HasFactory;

    protected $table = 'inv_gate_pass_items';

    protected $fillable = ['gate_pass_id', 'item_id', 'quantity', 'remarks'];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function gatePass(): BelongsTo
    {
        return $this->belongsTo(InvGatePass::class, 'gate_pass_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
