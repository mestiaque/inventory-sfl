<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvGrn extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_grns';

    protected $fillable = [
        'grn_number', 'purchase_order_id', 'source_type', 'store_id', 'supplier_id', 'buyer_id', 'style', 'order_ref',
        'challan_invoice_no', 'receive_date', 'status', 'total_amount', 'remarks', 'created_by', 'received_by',
    ];

    protected $casts = [
        'receive_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(InvPurchaseOrder::class, 'purchase_order_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'store_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(InvSupplier::class, 'supplier_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(InvBuyer::class, 'buyer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvGrnItem::class, 'grn_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
