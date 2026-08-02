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
        'gate_pass_no', 'gate_pass_date', 'buyer_id', 'vehicle_no', 'driver_name', 'driver_contact',
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

    public function shipments(): HasMany
    {
        return $this->hasMany(InvShipment::class, 'gate_pass_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
