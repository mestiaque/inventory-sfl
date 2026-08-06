<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvGatePass extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_gate_passes';

    protected $fillable = [
        'gate_pass_no', 'shipment_id', 'gate_pass_date', 'buyer_id', 'vehicle_no', 'driver_name', 'driver_contact',
        'store_id', 'status', 'remarks', 'created_by',
    ];

    protected $casts = [
        'gate_pass_date' => 'date',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(InvBuyer::class, 'buyer_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'store_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvGatePassItem::class, 'gate_pass_id');
    }

    /**
     * @deprecated legacy direction — a shipment created before the
     * shipment-first flow could point at a gate pass created before it.
     * New records use shipment() below instead.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(InvShipment::class, 'gate_pass_id');
    }

    /**
     * The shipment this gate pass was issued against — a gate pass is now
     * always the downstream document (Shipment entered first, Gate Pass
     * issued to let the goods physically leave the gate).
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(InvShipment::class, 'shipment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
