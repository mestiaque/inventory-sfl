<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasAudit;

class InvGatePassItem extends Model
{
    use HasAudit;
    use HasFactory;

    protected $table = 'inv_gate_pass_items';

    protected $fillable = ['gate_pass_id', 'item_id', 'quantity', 'carton_qty', 'remarks'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'carton_qty' => 'integer',
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
