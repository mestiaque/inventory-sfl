<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvStockAdjustment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_stock_adjustments';

    protected $fillable = [
        'adjustment_no', 'store_id', 'adjustment_date', 'type', 'status', 'remarks',
        'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'approved_at'     => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(InvStore::class, 'store_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvStockAdjustmentItem::class, 'adjustment_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
