<?php

namespace ME\SflInventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvBuyer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inv_buyers';

    protected $fillable = ['name', 'code', 'address', 'contact', 'is_active', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isReferenced(): bool
    {
        return InvFinishedGoodsReceive::where('buyer_id', $this->id)->exists()
            || InvGatePass::where('buyer_id', $this->id)->exists()
            || InvShipment::where('buyer_id', $this->id)->exists();
    }
}
