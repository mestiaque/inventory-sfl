<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvShipment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_shipments';

    protected $fillable = [
        'shipment_no', 'shipment_date', 'buyer_id', 'invoice_no', 'packing_list_no', 'gate_pass_id',
        'store_id', 'status', 'remarks', 'created_by',
    ];

    protected $casts = [
        'shipment_date' => 'date',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(InvBuyer::class, 'buyer_id');
    }

    public function gatePass(): BelongsTo
    {
        return $this->belongsTo(InvGatePass::class, 'gate_pass_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'store_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvShipmentItem::class, 'shipment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
