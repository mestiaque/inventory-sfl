<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvStockTransfer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_stock_transfers';

    protected $fillable = [
        'transfer_no', 'from_store_id', 'to_store_id', 'transfer_date', 'status', 'requested_by',
        'approved_by', 'approved_at', 'received_by', 'received_at', 'remarks', 'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'approved_at'   => 'datetime',
        'received_at'   => 'datetime',
    ];

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'from_store_id');
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'to_store_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvStockTransferItem::class, 'transfer_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
