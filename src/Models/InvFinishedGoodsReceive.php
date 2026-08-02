<?php

namespace ME\SflInventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvFinishedGoodsReceive extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_finished_goods_receives';

    protected $fillable = ['receive_no', 'receive_date', 'style', 'buyer_id', 'order_ref', 'store_id', 'remarks', 'created_by'];

    protected $casts = [
        'receive_date' => 'date',
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
        return $this->hasMany(InvFinishedGoodsReceiveItem::class, 'fg_receive_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
